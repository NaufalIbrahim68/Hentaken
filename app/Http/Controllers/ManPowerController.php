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
    
    public function createHenkaten($id)
    {
        $man_power = ManPower::findOrFail($id);
        $stations = Station::all();
        
        return view('manpower.create_henkaten', compact('man_power', 'stations'));
    }

    public function storeHenkaten(Request $request, $id)
    {
        $request->validate([
            'shift' => 'required|in:1,2',
            'line_area' => 'required|string|max:255',
            'man_power_id_after' => 'nullable|string|max:255',
            'station_id_after' => 'nullable|exists:stations,id',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'keterangan' => 'nullable|string',
        ]);

        $man_power = ManPower::findOrFail($id);

        ManPowerHenkaten::create([
            'man_power_id' => $man_power->id,
            'station_id'   => $man_power->station_id,
            'shift'        => $request->shift,
            'nama'         => $man_power->nama,
            'line_area'    => $request->line_area,
            'man_power_id_after' => $request->man_power_id_after,
            'station_id_after'   => $request->station_id_after,
            'effective_date'     => $request->effective_date,
            'end_date'           => $request->end_date,
            'keterangan'         => $request->keterangan,
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