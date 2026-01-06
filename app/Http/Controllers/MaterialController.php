<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    /**
     * Menampilkan daftar material dengan pencarian dan pagination.
     */
   public function index(Request $request)
{
    $query = Material::with('station')->orderBy('id', 'desc');

    $selectedLineArea = $request->get('line_area');

        if (auth()->check()) {
            $role = auth()->user()->role;
            if ($role === 'Leader SMT') {
                $lineAreas = collect(['SMT L1', 'SMT L2']);
            } elseif ($role === 'Leader QC') {
                $lineAreas = collect(['Incoming']);
            } elseif ($role === 'Leader PPIC') {
                $lineAreas = collect(['Delivery']);
            } elseif ($role === 'Leader FA') {
                $lineAreas = Station::select('line_area')
                    ->where('line_area', 'like', 'FA L%')
                    ->distinct()
                    ->orderBy('line_area', 'asc')
                    ->pluck('line_area');
            } else {
                $lineAreas = Station::select('line_area')
                    ->whereNotNull('line_area')
                    ->distinct()
                    ->orderBy('line_area', 'asc')
                    ->pluck('line_area');
            }
        } else {
            $lineAreas = Station::select('line_area')
                ->whereNotNull('line_area')
                ->distinct()
                ->orderBy('line_area', 'asc')
                ->pluck('line_area');
        }

    if ($request->has('search') && $request->search !== '') {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('material_name', 'like', '%'.$search.'%')
              ->orWhere('station_code', 'like', '%'.$search.'%')
              ->orWhere('keterangan', 'like', '%'.$search.'%');
        });
    }

        if (auth()->check()) {
            $role = auth()->user()->role;
            if ($role === 'Leader SMT') {
                if ($selectedLineArea) {
                    $query->whereHas('station', function ($q) use ($selectedLineArea) {
                        $q->where('line_area', $selectedLineArea);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->whereIn('line_area', ['SMT L1', 'SMT L2']);
                    });
                }
            } elseif ($role === 'Leader QC') {
                if ($selectedLineArea) {
                    $query->whereHas('station', function ($q) use ($selectedLineArea) {
                        $q->where('line_area', $selectedLineArea);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'Incoming');
                    });
                }
            } elseif ($role === 'Leader PPIC') {
                if ($selectedLineArea) {
                    $query->whereHas('station', function ($q) use ($selectedLineArea) {
                        $q->where('line_area', $selectedLineArea);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'Delivery');
                    });
                }
            } elseif ($role === 'Leader FA') {
                if ($selectedLineArea) {
                    $query->whereHas('station', function ($q) use ($selectedLineArea) {
                        $q->where('line_area', $selectedLineArea);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'like', 'FA L%');
                    });
                }
            } elseif ($role === 'Leader FA') {
                if ($selectedLineArea) {
                    $query->whereHas('station', function ($q) use ($selectedLineArea) {
                        $q->where('line_area', $selectedLineArea);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'like', 'FA L%');
                    });
                }
            } elseif ($selectedLineArea) {
                $query->whereHas('station', function ($q) use ($selectedLineArea) {
                    $q->where('line_area', $selectedLineArea);
                });
            }
        } elseif ($selectedLineArea) {
            $query->whereHas('station', function ($q) use ($selectedLineArea) {
                $q->where('line_area', $selectedLineArea);
            });
        }

    $materials = $query->paginate(5);

    return view('materials.index', compact('materials', 'lineAreas', 'selectedLineArea'));
}

    /**
     * Menampilkan form untuk membuat material baru.
     */
   public function create()
    {
        // 1. Ambil daftar 'line_area' yang unik (distinct) dari tabel stations
        // 2. Gunakan pluck() untuk mengambil nilainya saja
        // 3. filter()->values() untuk mengatasi jika ada nilai null/kosong
        $lineAreas = Station::select('line_area')
                            ->distinct()
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area')
                            ->filter()
                            ->values();

        // 3. Kirim variabel $lineAreas ke view 'materials.create'
        return view('materials.create', compact('lineAreas'));
    }


    /**
     * Menyimpan material baru ke database.
     */
   public function store(Request $request)
    {
        $request->validate([
            'station_id' => 'required|exists:stations,id',
            'material_name' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'lampiran' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // max 2MB
        ]);

        $station = Station::find($request->station_id);

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('public/lampiran_material');
        }

       Material::create([
            'station_id' => $request->station_id,
            'station_code' => $station->station_code,
            'material_name' => $request->material_name,
            'keterangan' => $request->keterangan,
            'foto_path' => $lampiranPath, // <-- Ini kuncinya
            'status' => 'Pending',
        ]);
     return redirect()->route('materials.index')
       ->with('success', 'Data material berhasil ditambahkan & menunggu approval.');
    }


    /**
     * Menampilkan form untuk mengedit material.
     */
    public function edit(Material $material)
    {
        $stations = Station::all();
        return view('materials.edit', compact('material', 'stations'));
    }

    /**
     * Memperbarui data material di database.
     */
    public function update(Request $request, Material $material)
    {
        $request->validate([
            'station_id' => 'required|exists:stations,id',
            'material_name' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'lampiran' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|boolean',
        ]);

        $station = Station::find($request->station_id);

        $lampiranPath = $material->lampiran;
        if ($request->hasFile('lampiran')) {
            // Hapus lampiran lama jika ada
            if ($material->lampiran) {
                Storage::delete($material->lampiran);
            }
            $lampiranPath = $request->file('lampiran')->store('public/lampiran_material');
        }

        $material->update([
            'station_id' => $request->station_id,
            'station_code' => $station->station_code,
            'material_name' => $request->material_name,
            'keterangan' => $request->keterangan,
            'lampiran' => $lampiranPath,
            'status' => $request->status,
        ]);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui.');
    }

    /**
     * Menghapus data material dari database.
     */
    public function destroy(Material $material)
    {
        // Hapus file lampiran dari storage
        if ($material->lampiran) {
            Storage::delete($material->lampiran);
        }

        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Data material berhasil dihapus.');
    }

    public function search(Request $request)
{
    $query = $request->get('q');

    $materials = \App\Models\Material::where('material_name', 'LIKE', "%{$query}%")
        ->limit(10)
        ->get(['id', 'material_name']);

    return response()->json($materials);
}

public function getStationsByLineArea(Request $request)
    {
        // Ambil 'line_area' dari query string (?line_area=...)
        $lineArea = $request->query('line_area');

        if (!$lineArea) {
            return response()->json([], 400); // Bad request jika parameter tidak ada
        }

        // Cari semua station yang cocok dengan line_area tersebut
        $stations = Station::where('line_area', $lineArea)
                           // ->where('status', 1) // Opsional: jika station punya status
                           ->orderBy('station_name', 'asc')
                           ->get(['id', 'station_name']); // Ambil ID dan Nama Station
        
        // Kembalikan data sebagai JSON
        return response()->json($stations);
    }

}