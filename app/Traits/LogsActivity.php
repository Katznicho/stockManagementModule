<?php

// app/Traits/LogsActivity.php

namespace App\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                $model->logActivity($event);
            });
        }
    }

    public function logActivity($action)
    {
        $userId = Auth::id(); // or manually passed in

        $changes = [
            'old' => $action === 'updated' ? $this->getOriginal() : null,
            'new' => $this->getAttributes(),
        ];

        Log::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'changed_data' => $changes,
            'description' => "{$action} on " . class_basename($this),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}
