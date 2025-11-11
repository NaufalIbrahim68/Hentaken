<?php

namespace App\Observers;

use App\Models\ManPower;
use App\Models\ActivityLog; 
use Illuminate\Support\Facades\Auth;

class ManPowerObserver
{
    /**
     * Handle the ManPower "created" event.
     * Ini dijalankan SETELAH data baru (tambah data) berhasil disimpan.
     */
    public function created(ManPower $manPower): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(), // Mengambil ID user yang sedang login
            'loggable_type' => ManPower::class,
            'loggable_id' => $manPower->id,
            'action' => 'created',
            'details' => [
                'message' => "Data man power '{$manPower->nama}' telah dibuat.",
                'new_data' => $manPower->getAttributes(), // Simpan semua data baru
            ],
        ]);
    }

    /**
     * Handle the ManPower "updated" event.
     * Ini dijalankan SETELAH data (edit data) berhasil diperbarui.
     */
    public function updated(ManPower $manPower): void
    {
        $changes = [];
        // Loop semua data yang "kotor" (berubah)
        foreach ($manPower->getDirty() as $key => $newValue) {
            // Kita tidak perlu mencatat perubahan 'updated_at'
            if ($key === 'updated_at') {
                continue;
            }

            $oldValue = $manPower->getOriginal($key);
            
            // Catat perubahan
            $changes[$key] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        // Hanya buat log jika ada perubahan
        if (empty($changes)) {
            return;
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => ManPower::class,
            'loggable_id' => $manPower->id,
            'action' => 'updated',
            'details' => [
                'message' => "Data man power '{$manPower->nama}' telah diperbarui.",
                'changes' => $changes, // Simpan detail perubahan
            ],
        ]);
    }

    /**
     * Handle the ManPower "deleted" event.
     * (Bonus) Ini untuk mencatat jika ada yang menghapus data.
     */
    public function deleted(ManPower $manPower): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => ManPower::class,
            'loggable_id' => $manPower->id,
            'action' => 'deleted',
            'details' => [
                'message' => "Data man power '{$manPower->nama}' (ID: {$manPower->id}) telah dihapus.",
                'deleted_data' => $manPower->getAttributes(), // Simpan data yang dihapus
            ],
        ]);
    }

    /**
     * Handle the ManPower "restored" event.
     */
    public function restored(ManPower $manPower): void
    {
        // Opsional: jika Anda menggunakan Soft Deletes
    }

    /**
     * Handle the ManPower "force deleted" event.
     */
    public function forceDeleted(ManPower $manPower): void
    {
        // Opsional: jika Anda menggunakan Soft Deletes
    }
}