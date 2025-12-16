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
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class MasterConfirmController extends Controller
{
    public function index()
    {
        // Get the authenticated user's role
        $user = Auth::user();
        $role = $user ? $user->role : null;

        // Determine line_area filter based on role
        $lineArea = null;
        if ($role === 'Sect Head QC') {
            $lineArea = 'Incoming';
        } elseif ($role === 'Sect Head PPIC') {
            $lineArea = 'Delivery';
        }
        // For other roles (e.g., Sect Head Produksi), $lineArea remains null (no filtering)

        // DEBUG: Log to check what's happening
        \Log::info('Master Confirm Index - Role: ' . $role . ', Line Area Filter: ' . ($lineArea ?? 'NONE'));

        // Query ManPower with optional line_area filtering
        if ($lineArea) {
            $manpowers = ManPower::where('status', 'Pending')
                ->whereHas('station', function ($q) use ($lineArea) {
                    $q->where('line_area', $lineArea);
                })
                ->with('station')
                ->get();
        } else {
            $manpowers = ManPower::where('status', 'Pending')->with('station')->get();
        }

        // Query Method with optional line_area filtering
        if ($lineArea) {
            $methods = Method::where('status', 'Pending')
                ->whereHas('station', function ($q) use ($lineArea) {
                    $q->where('line_area', $lineArea);
                })
                ->with('station')
                ->get();
        } else {
            $methods = Method::where('status', 'Pending')->with('station')->get();
        }

        // Query Machine with optional line_area filtering
        if ($lineArea) {
            $machines = Machine::where('status', 'Pending')
                ->whereHas('station', function ($q) use ($lineArea) {
                    $q->where('line_area', $lineArea);
                })
                ->with('station')
                ->get();
        } else {
            $machines = Machine::where('status', 'Pending')->with('station')->get();
        }

        // Query Material with optional line_area filtering
        if ($lineArea) {
            $materials = Material::where('status', 'Pending')
                ->whereHas('station', function ($q) use ($lineArea) {
                    $q->where('line_area', $lineArea);
                })
                ->with('station')
                ->get();
        } else {
            $materials = Material::where('status', 'Pending')->with('station')->get();
        }

        // DEBUG: Log counts
        \Log::info('Counts - ManPower: ' . $manpowers->count() . ', Methods: ' . $methods->count() . ', Machines: ' . $machines->count() . ', Materials: ' . $materials->count());

        return view('secthead.master-confirm', compact('manpowers', 'methods', 'machines', 'materials', 'role', 'lineArea'));
    }

    public function approve($type, $id)
    {
        $model = $this->getModel($type)::findOrFail($id);
        $model->status = 'Approved';
        $model->save();

        // Update status di activity log yang sudah ada
        $log = ActivityLog::where('loggable_type', get_class($model))
            ->where('loggable_id', $model->id)
            ->where('action', 'created')
            ->first();
        
        if ($log) {
            $details = $log->details;
            $details['status'] = 'Approved';
            $log->details = $details;
            $log->save();
        }

        return back()->with('success', ucfirst($type).' disetujui.');
    }

    public function revisi($type, $id)
    {
        $model = $this->getModel($type)::findOrFail($id);
        $model->status = 'Revisi';
        $model->save();

        // Update status di activity log yang sudah ada
        $log = ActivityLog::where('loggable_type', get_class($model))
            ->where('loggable_id', $model->id)
            ->where('action', 'created')
            ->first();
        
        if ($log) {
            $details = $log->details;
            $details['status'] = 'Revisi';
            $log->details = $details;
            $log->save();
        }

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