<?php


namespace App\Http\Controllers;

use App\Models\ManPower;
use App\Models\Method;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Station;
use Illuminate\Http\Request;
use App\Models\Role;

// --- 1. TAMBAHKAN INI ---
use Illuminate\Support\Facades\Storage;

class MasterConfirmController extends Controller
{
    public function index()
    {
        $manpowers = ManPower::where('status', 'Pending')->get();
        $methods   = Method::where('status', 'Pending')->get();
        $machines  = Machine::where('status', 'Pending')->get();
        $materials = Material::where('status', 'Pending')->get();

        return view('secthead.master-confirm', compact('manpowers', 'methods', 'machines', 'materials'));
    }

    public function approve($type, $id)
    {
        $model = $this->getModel($type)::findOrFail($id);
        $model->status = 'Approved';
        $model->save();

        return back()->with('success', ucfirst($type).' disetujui.');
    }

    public function revisi($type, $id)
    {
        $model = $this->getModel($type)::findOrFail($id);
        $model->status = 'Revisi';
        $model->save();

        return back()->with('warning', ucfirst($type).' dikembalikan untuk revisi.');
    }

    private function getModel($type)
    {
        return match($type) {
            'manpower' => ManPower::class,
            'method'   => Method::class,
            'machine'  => Machine::class,
            'material' => Material::class,
            'role'     => Role::class, 
            default    => abort(404),
        };
    }

    /**
     * Tampilkan detail data untuk modal (INI YANG DIUBAH)
     */
    public function show($type, $id)
    {
        $modelClass = $this->getModel($type);
        $query = $modelClass::query();

        // Eager load relasi 'station' untuk manpower
        if ($type === 'manpower') {
            $query->with('station');
        }

        // --- 2. TAMBAHKAN BLOK INI ---
        // Eager load relasi 'station' untuk material (jika perlu)
        if ($type === 'material') {
            // Asumsi: Di model Material.php, ada relasi `public function station()`
            $query->with('station'); 
        }
        // -----------------------------

        $model = $query->findOrFail($id);

        // --- 3. TAMBAHKAN BLOK INI ---
        // Setelah model diambil, cek apakah tipenya material
        // Jika ya, buat URL publik untuk lampiran
        if ($type === 'material') {
            
            // Cek jika 'foto_path' (sesuai DB) ada isinya
            if ($model->foto_path) {
                
                // Buat property baru 'lampiran_url' yang akan dikirim via JSON
                // Storage::url() akan mengubah 'public/...' menjadi '/storage/...'
                $model->lampiran_url = Storage::url($model->foto_path);
                
            } else {
                // Jika tidak ada foto, kirim null
                $model->lampiran_url = null;
            }
        }
        // -----------------------------

        return response()->json($model);
    }

}