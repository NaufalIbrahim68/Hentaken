<?php

namespace App\Observers;

use App\Models\Method;
use App\Models\MasterDataLog;
use Illuminate\Support\Facades\Auth;

class MethodObserver
{
    public function created(Method $method): void
    {
        MasterDataLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Method::class,
            'loggable_id' => $method->id,
            'action' => 'created',
            'details' => [
                'message' => "Data method '{$method->name}' telah dibuat.",
                'name' => $method->name,
                'line_area' => $method->station->line_area ?? '-',
                'new_data' => $method->getAttributes(),
            ],
        ]);
    }

    public function updated(Method $method): void
    {
        $changes = [];
        foreach ($method->getDirty() as $key => $newValue) {
            if ($key === 'updated_at') {
                continue;
            }

            $oldValue = $method->getOriginal($key);
            $changes[$key] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        if (empty($changes)) {
            return;
        }

        MasterDataLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Method::class,
            'loggable_id' => $method->id,
            'action' => 'updated',
            'details' => [
                'message' => "Data method '{$method->name}' telah diperbarui.",
                'name' => $method->name,
                'line_area' => $method->station->line_area ?? '-',
                'changes' => $changes,
            ],
        ]);
    }

    public function deleted(Method $method): void
    {
        MasterDataLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Method::class,
            'loggable_id' => $method->id,
            'action' => 'deleted',
            'details' => [
                'message' => "Data method '{$method->name}' telah dihapus.",
                'name' => $method->name,
                'line_area' => $method->station->line_area ?? '-',
                'deleted_data' => $method->getAttributes(),
            ],
        ]);
    }
}
