<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MachineController extends Controller
{
    /**
     * Menampilkan daftar master data machine.
     */
    public function index(Request $request)
    {
        $query = Machine::with('station')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('deskripsi', 'like', '%' . $search . '%')
                  ->orWhere('keterangan', 'like', '%' . $search . '%')
                  ->orWhereHas('station', function ($q) use ($search) {
                      $q->where('station_code', 'like', '%' . $search . '%');
                  });
        }

        $machines = $query->paginate(15);
        return view('machines.index', compact('machines'));
    }

    /**
     * Menampilkan form untuk membuat data machine baru.
     */
    public function create()
    {
        $stations = Station::orderBy('station_code')->get();
        return view('machines.create', compact('stations'));
    }

    /**
     * Menyimpan data machine baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'station_id' => 'required|exists:stations,id',
            'deskripsi' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'foto_path' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Maks 2MB
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto_path')) {
            $fotoPath = $request->file('foto_path')->store('public/foto_machines');
        }

        Machine::create([
            'station_id' => $request->station_id,
            'deskripsi' => $request->deskripsi,
            'keterangan' => $request->keterangan,
            'foto_path' => $fotoPath,
        ]);

        return redirect()->route('machines.index')->with('success', 'Data mesin berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit data machine.
     */
    public function edit(Machine $machine)
    {
        $stations = Station::orderBy('station_code')->get();
        return view('machines.edit', compact('machine', 'stations'));
    }

    /**
     * Memperbarui data machine di database.
     */
    public function update(Request $request, Machine $machine)
    {
        $request->validate([
            'station_id' => 'required|exists:stations,id',
            'deskripsi' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'foto_path' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $fotoPath = $machine->foto_path;
        if ($request->hasFile('foto_path')) {
            // Hapus foto lama jika ada
            if ($machine->foto_path) {
                Storage::delete($machine->foto_path);
            }
            $fotoPath = $request->file('foto_path')->store('public/foto_machines');
        }

        $machine->update([
            'station_id' => $request->station_id,
            'deskripsi' => $request->deskripsi,
            'keterangan' => $request->keterangan,
            'foto_path' => $fotoPath,
        ]);

        return redirect()->route('machines.index')->with('success', 'Data mesin berhasil diperbarui.');
    }

    /**
     * Menghapus data machine dari database.
     */
    public function destroy(Machine $machine)
    {
        // Hapus file foto dari storage
        if ($machine->foto_path) {
            Storage::delete($machine->foto_path);
        }

        $machine->delete();

        return redirect()->route('machines.index')->with('success', 'Data mesin berhasil dihapus.');
    }
}