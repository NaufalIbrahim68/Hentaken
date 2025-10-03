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
        $query = Material::with('station')->latest();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('material_name', 'like', '%'.$search.'%')
                  ->orWhere('station_code', 'like', '%'.$search.'%')
                  ->orWhere('keterangan', 'like', '%'.$search.'%');
            });
        }

        $materials = $query->paginate(15);
        return view('materials.index', compact('materials'));
    }

    /**
     * Menampilkan form untuk membuat material baru.
     */
    public function create()
    {
        $stations = Station::all();
        return view('materials.create', compact('stations'));
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
            'status' => 'required|boolean',
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
            'lampiran' => $lampiranPath,
            'status' => $request->status,
        ]);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil ditambahkan.');
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
}