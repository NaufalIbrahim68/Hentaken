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
        

        // 1. UBAH VALIDASI: Kita validasi ID yang dikirim dari hidden input.
        // Ini lebih aman dan efisien.
        $validated = $request->validate([
            'shift'              => 'required|in:1,2',
            'line_area'          => 'required|string|max:255',
            'effective_date'     => 'required|date',
            'end_date'           => 'nullable|date|after_or_equal:effective_date',

            // Validasi ID Karyawan "Before" dari hidden input
            'man_power_id'       => 'required|exists:man_power,id',

            // Validasi ID Karyawan "After" dari hidden input
            'man_power_id_after' => 'required|exists:man_power,id',


            'keterangan'         => 'nullable|string',
            'lampiran'           => 'nullable|image|mimes:jpeg,png|max:2048',
            
            // Validasi untuk teks nama (opsional, tapi baik untuk ada)
            'nama'               => 'required|string|max:255',
            'nama_after'         => 'required|string|max:255',
        ], [
            // Pesan error yang lebih ramah pengguna
            'man_power_id.required'       => 'Karyawan sebelumnya wajib dipilih dari daftar.',
            'man_power_id.exists'         => 'Karyawan sebelumnya tidak valid.',
            'man_power_id_after.required' => 'Karyawan sesudah wajib dipilih dari daftar.',
            'man_power_id_after.exists'   => 'Karyawan sesudah tidak valid.',
        ]);

       
        // Upload lampiran jika ada
        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('lampiran_henkaten', 'public');
        }

        // 3. GUNAKAN ID LANGSUNG: Saat menyimpan, gunakan ID dari request
        // yang sudah tervalidasi.
        ManPowerHenkaten::create([
            'man_power_id'       => $validated['man_power_id'],       // Gunakan ID langsung
            'man_power_id_after' => $validated['man_power_id_after'], // Gunakan ID langsung
            
           
            'shift'              => $validated['shift'],
            'line_area'          => $validated['line_area'],
            'effective_date'     => $validated['effective_date'],
            'end_date'           => $validated['end_date'],
            'keterangan'         => $validated['keterangan'],
            'lampiran'           => $lampiranPath,
            'nama'               => $validated['nama'],
            'nama_after'         => $validated['nama_after'],
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