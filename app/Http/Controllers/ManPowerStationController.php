<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use App\Models\ManPowerManyStation;
use App\Models\Station;
use Illuminate\Http\Request;

class ManPowerStationController extends Controller
{

    public function matrixApprovalIndex()
    {
        // Mengambil semua record dari tabel man_power_many_stations
        // yang statusnya 'PENDING', sekaligus memuat data Operator (manpower) dan Station.
        $manpowerStations = ManPowerManyStation::where('status', 'PENDING')
            ->with(['manpower', 'station'])
            ->get();

        // Mengirim data ke view yang telah direvisi sebelumnya (secthead.approval-matrix-index)
        return view('secthead.approval-matrix-index', [
            'manpowerStations' => $manpowerStations
        ]);
    }

    /**
     * Menampilkan detail pengajuan OMM (Digunakan oleh API/Modal Detail).
     * Endpoint: /api/omm-detail/{id}
     */
    public function showOmmDetail($id)
    {
        // Ambil data OMM beserta relasi ManPower dan Station
        $detail = ManPowerManyStation::with(['manpower', 'station'])->find($id);

        if (!$detail) {
            return response()->json(['error' => 'Data OMM tidak ditemukan.'], 404);
        }

        return response()->json($detail);
    }

    /**
     * Mengubah status pengajuan OMM menjadi 'Approved'.
     * Endpoint: /approval/omm/{id}/approve
     */
   public function approveOmm($id)
{
    $omm = ManPowerManyStation::findOrFail($id);

    // Jika sudah Approved → tidak error
    if ($omm->status === 'Approved') {
        return redirect()->route('approval.omm.index')
            ->with('success', 'Status OMM sudah Approved sebelumnya.');
    }

    // Jika status bukan PENDING dan bukan Approved → error valid
    if ($omm->status !== 'PENDING') {
        return back()->with('error', 'Status OMM sudah bukan PENDING.');
    }

    // Approve jika masih PENDING
    $omm->update([
        'status'      => 'Approved',
        'approved_by' => auth()->id(),
        'approved_at' => now(),
    ]);

    return redirect()->route('approval.omm.index')
        ->with('success', 'Pengajuan One Man Can Many Stations berhasil di-Approve.');
}


    
    public function reviseOmm(Request $request, $id)
{
    $request->validate([
        'revision_notes' => 'required|string|max:500',
    ]);

    $omm = ManPowerManyStation::findOrFail($id);

    // Hanya boleh revisi jika masih PENDING
    if ($omm->status !== 'PENDING') {
        return back()->with('error', 'Status OMM sudah bukan PENDING.');
    }

    $omm->update([
        'status'         => 'Revision',
        'revision_notes' => $request->revision_notes,
        'approved_by'    => auth()->id(),
        'approved_at'    => now(),
    ]);

    return redirect()->route('approval.omm.index')
        ->with('success', 'Pengajuan One Man Can Many Stations berhasil di-Revisi.');
}


    public function stationsByLine(Request $request)
    {
        return Station::where('line_area', $request->line_area)->get();
    }

    // POST: Tambahkan relasi
   public function store(Request $request)
{
    $request->validate([
        'man_power_id' => 'required|integer|exists:man_power,id',
        'station_id'   => 'required|integer|exists:stations,id',
    ]);

    ManPowerManyStation::create([
        'man_power_id' => $request->man_power_id,
        'station_id'   => $request->station_id,
        'status'       => 'PENDING',      // default wajib
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    return response()->json(['success' => true]);
}

}
