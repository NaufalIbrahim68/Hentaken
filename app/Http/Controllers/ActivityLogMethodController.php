<?php

namespace App\Http\Controllers;

use App\Models\Method;
use App\Models\Station;
use Illuminate\Http\Request;
use App\Models\MethodHenkaten;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf; 


class ActivityLogMethodController extends Controller
{
   

public function index(Request $request)
{
    // Ambil input filter
    $created_date = $request->input('created_date');
    $line_area = $request->input('line_area'); // <-- Tambahan

    // Asumsi model Anda bernama MethodHenkaten
    $query = \App\Models\MethodHenkaten::with('station'); 

    if ($created_date) {
        $query->whereDate('created_at', $created_date);
    }

    // <-- TAMBAHAN: Ambil data unik line_area untuk dropdown
        if (auth()->check()) {
            $role = auth()->user()->role;
            if ($role === 'Leader SMT') {
                $lineAreas = Station::select('line_area')
                    ->where('line_area', 'like', 'SMT%')
                    ->distinct()
                    ->orderBy('line_area', 'asc')
                    ->pluck('line_area');
            } elseif ($role === 'Leader QC') {
                $lineAreas = collect(['Incoming']);
            } elseif ($role === 'Leader PPIC') {
                $lineAreas = collect(['Delivery']);
            } elseif ($role === 'Leader FA') {
                $lineAreas = Station::distinct()->where('line_area', 'like', 'FA L%')->pluck('line_area');
            } else {
                $lineAreas = Station::distinct()->whereNotNull('line_area')->pluck('line_area');
            }
        } else {
            $lineAreas = Station::distinct()->whereNotNull('line_area')->pluck('line_area');
        }

        if (auth()->check()) {
            $role = auth()->user()->role;
            if ($role === 'Leader SMT') {
                if ($line_area) {
                    $query->whereHas('station', function ($q) use ($line_area) {
                        $q->where('line_area', $line_area);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'like', 'SMT%');
                    });
                }
            } elseif ($role === 'Leader QC') {
                if ($line_area) {
                    $query->whereHas('station', function ($q) use ($line_area) {
                        $q->where('line_area', $line_area);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'Incoming');
                    });
                }
            } elseif ($role === 'Leader PPIC') {
                if ($line_area) {
                    $query->whereHas('station', function ($q) use ($line_area) {
                        $q->where('line_area', $line_area);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'Delivery');
                    });
                }
            } elseif ($role === 'Leader FA') {
                if ($line_area) {
                    $query->whereHas('station', function ($q) use ($line_area) {
                        $q->where('line_area', $line_area);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'like', 'FA L%');
                    });
                }
            } elseif ($line_area) {
                $query->whereHas('station', function ($q) use ($line_area) {
                    $q->where('line_area', $line_area);
                });
            }
        } elseif ($line_area) {
            $query->whereHas('station', function ($q) use ($line_area) {
                $q->where('line_area', $line_area);
            });
        }

        $logs = $query->latest('created_at')
            ->paginate(10)
            ->appends($request->query());

    return view('methods.activity-log', [ // Pastikan nama view-nya benar
        'logs' => $logs,
        'created_date' => $created_date,
        'lineAreas' => $lineAreas,     // <-- Kirim ke view
        'line_area' => $line_area,         // <-- Kirim ke view
    ]);
}

    // ==========================================================
    // TAMBAHKAN FUNGSI-FUNGSI BARU DI BAWAH INI
    // ==========================================================

    /**
     * Menampilkan form untuk mengedit log.
     */
 public function edit(MethodHenkaten $log)
{
    // 1. Ambil semua line_area
    $lineAreas = Station::select('line_area')
                        ->distinct()
                        ->orderBy('line_area', 'asc')
                        ->pluck('line_area');

    // 2. Ambil semua station berdasarkan line_area milik log
    $stations = Station::where('line_area', $log->station->line_area)
                       ->orderBy('station_name', 'asc')
                       ->get();

    // 3. Preload METHOD berdasarkan station yg sedang diedit
    //    Ini penting untuk halaman EDIT agar dropdown method terisi
    $methodList = Method::where('station_id', $log->station_id) 
                            ->orderBy('methods_name', 'asc')
                            ->get();

    // 4. Tentukan apakah role ini predefined (static)
    $userRole = auth()->user()->role;
    $isPredefinedRole = in_array($userRole, ['Leader FA', 'SubLeader FA', 'Leader QC', 'Leader PPIC']);

    // 5. Kirim semua data ke Blade
    return view('methods.create_henkaten', [
        'log'               => $log,
        'lineAreas'         => $lineAreas,
        'stations'          => $stations,
        'methodList'        => $methodList,
        'isPredefinedRole'  => $isPredefinedRole,
    ]);
}

    /**
     * Memperbarui log di database.
     */
   public function update(Request $request, MethodHenkaten $log)
{
    // Validasi data
    $request->validate([
        'keterangan_after' => 'nullable|string|max:255',
        'line_area' => 'nullable|string|max:100',
        'effective_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:effective_date',
        
        'lampiran'   => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
        'lampiran_2' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
        'lampiran_3' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',

        'serial_number_start' => 'required|string|max:255',
        'serial_number_end'   => 'required|string|max:255',
    ]);

    // Ambil semua data kecuali file upload
    $data = $request->except(['lampiran', 'lampiran_2', 'lampiran_3']);

    // Set status kembali menjadi Pending
    $data['status'] = 'Pending';
    $data['note'] = null;


    // ==========================================================
    // 🔥 UPLOAD FILE: lampiran
    // ==========================================================
    if ($request->hasFile('lampiran')) {
        if ($log->lampiran) {
            Storage::delete('public/' . $log->lampiran);
        }

        $path = $request->file('lampiran')->store('lampiran/method', 'public');
        $data['lampiran'] = $path;
    }

    // ==========================================================
    // 🔥 UPLOAD FILE: lampiran_2
    // ==========================================================
    if ($request->hasFile('lampiran_2')) {
        if ($log->lampiran_2) {
            Storage::delete('public/' . $log->lampiran_2);
        }

        $path2 = $request->file('lampiran_2')->store('lampiran/method', 'public');
        $data['lampiran_2'] = $path2;
    }

    // ==========================================================
    // 🔥 UPLOAD FILE: lampiran_3
    // ==========================================================
    if ($request->hasFile('lampiran_3')) {
        if ($log->lampiran_3) {
            Storage::delete('public/' . $log->lampiran_3);
        }

        $path3 = $request->file('lampiran_3')->store('lampiran/method', 'public');
        $data['lampiran_3'] = $path3;
    }


    // Update data utama
    $log->update($data);

    return redirect()->route('activity.log.method')
                     ->with('success', 'Data berhasil diupdate dan diajukan kembali untuk approval.');
}

    /**
     * Menghapus log dari database.
     */
    public function destroy(MethodHenkaten $log) // <-- GANTI INI jika model Anda berbeda
    {
        // Hapus lampiran dari storage jika ada
        if ($log->lampiran) {
            Storage::delete('public/' . $log->lampiran);
        }

        // Hapus data dari database
        $log->delete();

        return redirect()->route('activity.log.method')
                         ->with('success', 'Data log berhasil dihapus.');
    }

  public function downloadPDF(Request $request)
{
    // Ambil input filter (LOGIKA SAMA DENGAN METHOD MANPOWER)
    $created_date = $request->input('created_date');
    $line_area = $request->input('line_area');

    // UBAH: Menggunakan model MethodHenkaten
    // Pastikan nama model Anda sudah benar (misal: \App\Models\MethodHenkaten)
    $query = \App\Models\MethodHenkaten::with('station');

    // Filter Tanggal (Sama persis)
    if ($created_date) {
        $query->whereDate('created_at', $created_date);
    }

    // Filter Line Area (Sama persis)
    // Ini mengasumsikan 'methods_henkaten' punya kolom 'line_area'
    if ($line_area) {
        $query->where('line_area', $line_area);
    }

    // Ambil SEMUA data (Sama persis)
    $logs = $query->latest('created_at')->get();

    // Data untuk dikirim ke view PDF (Sama persis)
    $data = [
        'logs' => $logs,
        'filterDate' => $created_date,
        'filterLine' => $line_area,
    ];

    // UBAH: Menggunakan view PDF 'method'
    $pdf = Pdf::loadView('pdf.activity-log-method', $data) 
                ->setPaper('a4', 'landscape');
    
    // UBAH: Nama file download
    return $pdf->download('Laporan_Henkaten_Method.pdf');
}
}
