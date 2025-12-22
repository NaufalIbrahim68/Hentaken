<?php

namespace App\Observers;

use App\Models\Machine;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class MachineObserver
{
    public function created(Machine $machine): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Machine::class,
            'loggable_id' => $machine->id,
            'action' => 'created',
            'details' => [
                'message' => "Data machine '{$machine->deskripsi}' telah dibuat.",
                'deskripsi' => $machine->deskripsi,
                'line_area' => $machine->station->line_area ?? '-',
                'new_data' => $machine->getAttributes(),
            ],
        ]);
    }

    public function updated(Machine $machine): void
    {
        $changes = [];
        foreach ($machine->getDirty() as $key => $newValue) {
            if ($key === 'updated_at') {
                continue;
            }

            $oldValue = $machine->getOriginal($key);
            $changes[$key] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        if (empty($changes)) {
            return;
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Machine::class,
            'loggable_id' => $machine->id,
            'action' => 'updated',
            'details' => [
                'message' => "Data machine '{$machine->deskripsi}' telah diperbarui.",
                'deskripsi' => $machine->deskripsi,
                'line_area' => $machine->station->line_area ?? '-',
                'changes' => $changes,
            ],
        ]);
    }

    public function deleted(Machine $machine): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Machine::class,
            'loggable_id' => $machine->id,
            'action' => 'deleted',
            'details' => [
                'message' => "Data machine '{$machine->deskripsi}' telah dihapus.",
                'deskripsi' => $machine->deskripsi,
                'line_area' => $machine->station->line_area ?? '-',
                'deleted_data' => $machine->getAttributes(),
            ],
        ]);
    }
}
