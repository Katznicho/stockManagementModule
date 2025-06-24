<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StockSaleController extends Controller
{
    public function index()
    {
        $externalId = auth()->user()->entity_id;

        if (!$externalId) {
            return redirect()->back()->with('error', 'External ID is required.');
        }

        try {
            $baseUrl = config('services.stock_api.base_url');
            $url = "{$baseUrl}/getSalesByExternalId/{$externalId}";
            $response = Http::get($url);

            if ($response->failed()) {
                return redirect()->back()->with('error', 'Failed to fetch sales data');
            }

            if ($response->successful()) {
                $sales = $response->json()['data'];
                // Map the sales data to include only required fields
                $salesData = collect($sales)->map(function ($sale) {
                    return [
                        'item_name' => $sale['item']['name'] ?? 'N/A', // Access name from item relationship
                        'quantity' => $sale['quantity_suom'] ?? 0, // Use quantity_suom from sale
                        'price' => $sale['price'] ?? '0.00', // Use price from sale
                        'store_name' => $sale['item']['external_store_name'] ?? 'N/A', // Access external_store_name from item
                    ];
                })->toArray();

                return view('stock.sales.index', compact('salesData'));
            }

            return redirect()->back()->with('error', 'Failed to fetch sales data');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Error: ' . $th->getMessage());
        }
    }
}