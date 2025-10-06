<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Station;
use App\Models\ManPower;
use App\Models\ManPowerHenkaten; 

class ManPowerController extends Controller
{
    // ========================================
    // MASTER MAN POWER (Full CRUD)
    // ========================================
    
 public function index()
{
    
    $man_powers = ManPower::with('station')->orderBy('nama', 'asc')->paginate(5); 
    
    return view('manpower.index', compact('man_powers'));
}


  public function createMaster()
    {
        $stations = Station::all(); // Mengambil semua data station untuk dropdown
        return view('manpower.create', compact('stations'));
    }

    /**
     * Menyimpan data Man Power baru ke database.
     */
    public function storeMaster(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'station_id' => 'required|exists:stations,id',
            'shift' => 'required|in:1,2',
        ]);

        ManPower::create([
            'nama' => $request->nama,
            'station_id' => $request->station_id,
            'shift' => $request->shift,
        ]);

        return redirect()->route('manpower.index')
                         ->with('success', 'Data Man Power berhasil ditambahkan.');
    }


    public function editMaster($id)
    {
        $man_power = ManPower::findOrFail($id);
        $stations = Station::all();
        
        return view('manpower.edit_master', compact('man_power', 'stations'));
    }

    public function updateMaster(Request $request, $id)
{
   
    
    $validatedData = $request->validate([
        'nama' => 'required|string|max:255',
        'station_id' => 'required|exists:stations,id',
        'shift' => 'required|in:Shift A,Shift B', 
    ]);

    // 2. Cari data ManPower yang akan di-update
    $man_power = ManPower::findOrFail($id);
    
    // 3. Update data dengan data yang sudah tervalidasi
    // Menggunakan $validatedData lebih aman daripada $request->all()
    $man_power->update($validatedData);

    // 4. Redirect kembali ke halaman index dengan pesan sukses
    return redirect()->route('manpower.index')
        ->with('success', 'Data Man Power berhasil diperbarui.');
}

    public function destroyMaster($id)
    {
        $man_power = ManPower::findOrFail($id);
        $man_power->delete();

        return redirect()->route('manpower.index')
            ->with('success', 'Data Man Power berhasil dihapus.');
    }

    // ========================================
    // HENKATEN (Create & Delete ONLY, NO EDIT)
    // ========================================

    public function createHenkatenForm()
{
    // misalnya ambil man power pertama sebagai default
    $man_power = ManPower::with('station')->first();

    $stations = Station::all();

    return view('manpower.create_henkaten', compact('man_power', 'stations'));
}

    
    public function createHenkaten($id)
    {
        $man_power = ManPower::findOrFail($id);
        $stations = Station::all();
        
        return view('manpower.create_henkaten', compact('man_power', 'stations'));
    }




public function storeHenkaten(Request $request)
{
    $request->validate([
        'nama'         => 'required|string|max:255', // nama_before
        'nama_after'   => 'required|string|max:255',
        'shift'        => 'required|in:1,2',
        'line_area'    => 'required|string|max:255',
        'station_id_after' => 'nullable|exists:stations,id',
        'effective_date'   => 'nullable|date',
        'end_date'         => 'nullable|date|after_or_equal:effective_date',
        'keterangan'       => 'nullable|string',
        'lampiran'         => 'nullable|image|mimes:jpeg,png|max:2048',
    ]);

    // Cari man_power_id dari nama_before
    $manPowerBefore = ManPower::where('nama', $request->nama)->first();
    if (!$manPowerBefore) {
        return back()->withErrors(['nama' => 'Karyawan dengan nama ini tidak ditemukan.'])->withInput();
    }

    // Cari man_power_id dari nama_after
    $manPowerAfter = ManPower::where('nama', $request->nama_after)->first();
    if (!$manPowerAfter) {
        return back()->withErrors(['nama_after' => 'Karyawan dengan nama sesudah tidak ditemukan.'])->withInput();
    }

    // Upload lampiran jika ada
    $lampiranPath = null;
    if ($request->hasFile('lampiran')) {
        $lampiranPath = $request->file('lampiran')->store('lampiran_henkaten', 'public');
    }

    // Simpan data Henkaten
    ManPowerHenkaten::create([
        'man_power_id'       => $manPowerBefore->id,  // ID karyawan sebelum
        'man_power_id_after' => $manPowerAfter->id,   // ID karyawan sesudah
        'station_id'         => $request->station_id_after,
        'station_id_after'   => $request->station_id_after,
        'shift'              => $request->shift,
        'nama'               => $request->nama,
        'nama_after'         => $request->nama_after,
        'line_area'          => $request->line_area,
        'effective_date'     => $request->effective_date,
        'end_date'           => $request->end_date,
        'keterangan'         => $request->keterangan,
        'lampiran'           => $lampiranPath,
    ]);

    return redirect()->route('manpower.index')
        ->with('success', 'Data Henkaten berhasil disimpan.');
}
    public function destroy($id)
    {
        $henkaten = ManPowerHenkaten::findOrFail($id);
        $henkaten->delete();
        
        return redirect()->route('manpower.index')
            ->with('success', 'Data Henkaten berhasil dihapus.');
    }
}