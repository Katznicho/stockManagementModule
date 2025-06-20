use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

public function confirmAndProceed()
{
    Log::info("confirmAndProceed method invoked for sale_id: {$this->sale_id}");
    DB::beginTransaction();
    try {
        $saleItems = $this->getTableQuery()->get();
        // dd($saleItems->toArray()); // Uncomment for debugging
        Log::info('Fetched sale items for confirmation: ', ['count' => $saleItems->count()]);
        if ($saleItems->isEmpty()) {
            throw new \Exception('No sale items found to confirm.');
        }

        $isAdmin = $this->user->role->groups->contains('name', 'Administration');
        $isFinance = $this->user->role->groups->contains('name', 'Finance');

        $bulkReduceItems = [];
        foreach ($saleItems as $saleItem) {
            // Check if the user has already changed this sale item
            $changedByUsers = $saleItem->changed_by_users ?? [];
            if (!$isAdmin && !$isFinance && in_array($this->user->id, $changedByUsers)) {
                Log::warning("User ID {$this->user->id} has already changed SaleItem ID {$saleItem->SaleItemID}");
                Notification::make()
                    ->title('Already Changed')
                    ->body("You have already made changes to these Items.")
                    ->warning()
                    ->send();
                continue; // Skip this item but proceed with others
            }

            $pendingStatus = $this->pendingStatuses[$saleItem->SaleItemID] ?? null;
            if ($pendingStatus) {
                if ($isAdmin) {
                    // For admins, apply changes but don't execute transactions until approved
                    $updateData = [
                        'Status' => $pendingStatus === 'Status' ? 1 : 0,
                        'Partial' => $pendingStatus === 'Partial' ? true : false,
                        'UnDone' => $pendingStatus === 'UnDone' ? true : false,
                        'is_confirmed' => true,
                        'is_approved' => false,  // Requires final approval
                    ];
                    $saleItem->update($updateData);
                    Log::info("Admin user ID {$this->user->id} updated SaleItem ID {$saleItem->SaleItemID} with pending approval: ", $updateData);
                } else {
                    // For regular users, apply changes and execute transactions
                    $this->applyStatusChange($saleItem, $pendingStatus);
                    $saleItem->update(['is_confirmed' => true]);

                    // Prepare data for bulk stock reduction including price
                    $bulkReduceItems[] = [
                        'product_id' => $saleItem->ProductID,
                        'external_id' => $this->user->entity_id ?? 1, // Assume entity_id from user, fallback to 1
                        'quantity' => $saleItem->Quantity,
                        'external_store_id' => 1, // As specified
                        'price' => (float)$saleItem->Price, // Convert Price to float
                    ];
                }

                // Record the user who made the change
                $changedByUsers[] = $this->user->id;
                $saleItem->update(['changed_by_users' => array_unique($changedByUsers)]);
                Log::info("User ID {$this->user->id} recorded as having changed SaleItem ID {$saleItem->SaleItemID}");
            }
        }

        // Call reduceStockBulk endpoint if there are items to reduce
        if (!empty($bulkReduceItems) && !$isAdmin) {
            $baseUrl = config('services.stock_api.base_url');
            $url = "{$baseUrl}/reduceStockBulk";
            $response = Http::withHeaders(['Accept' => 'application/json'])->post(
                $url,
                ['items' => $bulkReduceItems]
            );

            if ($response->failed()) {
                Log::error('Failed to reduce stock bulk: ' . $response->body());
                throw new \Exception('Failed to reduce stock bulk: ' . $response->body());
            }

            Log::info('Successfully reduced stock bulk: ' . $response->body());
        }

        DB::commit();
        Log::info("Successfully confirmed sale items for sale_id: {$this->sale_id}");

        $dashboardUrl = route('dashboard', [], false);
        if (!$dashboardUrl) {
            Log::error('Dashboard route not found. Falling back to default URL.');
            return redirect()->route('dashboard');
        }

        return redirect()->route('dashboard');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Failed to confirm sale items: {$e->getMessage()}");
        Notification::make()
            ->title('Confirmation Failed')
            ->body($e->getMessage())
            ->danger()
            ->send();
        throw $e;
    }
}