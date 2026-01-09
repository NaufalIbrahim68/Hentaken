<?php

namespace App\Observers;

use App\Models\Material;
use App\Models\MasterDataLog;
use Illuminate\Support\Facades\Auth;

class MaterialObserver
{
    public function created(Material $material): void
    {
        MasterDataLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Material::class,
            'loggable_id' => $material->id,
            'action' => 'created',
            'details' => [
                'message' => "Data material '{$material->material_name}' telah dibuat.",
                'material_name' => $material->material_name,
                'station_name' => $material->station->station_name ?? '-',
                'line_area' => $material->station->line_area ?? '-',
                'status' => $material->status,
                'new_data' => $material->getAttributes(),
            ],
        ]);
    }

    public function updated(Material $material): void
    {
        $changes = [];
        foreach ($material->getDirty() as $key => $newValue) {
            if ($key === 'updated_at') {
                continue;
            }

            $oldValue = $material->getOriginal($key);
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
            'loggable_type' => Material::class,
            'loggable_id' => $material->id,
            'action' => 'updated',
            'details' => [
                'message' => "Data material '{$material->material_name}' telah diperbarui.",
                'material_name' => $material->material_name,
                'station_name' => $material->station->station_name ?? '-',
                'line_area' => $material->station->line_area ?? '-',
                'changes' => $changes,
            ],
        ]);
    }

    public function deleted(Material $material): void
    {
        MasterDataLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Material::class,
            'loggable_id' => $material->id,
            'action' => 'deleted',
            'details' => [
                'message' => "Data material '{$material->material_name}' telah dihapus.",
                'material_name' => $material->material_name,
                'station_name' => $material->station->station_name ?? '-',
                'line_area' => $material->station->line_area ?? '-',
                'deleted_data' => $material->getAttributes(),
            ],
        ]);
    }
}
