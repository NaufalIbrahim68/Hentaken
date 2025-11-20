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
        // yang statusnya 'Pending', sekaligus memuat data Operator (manpower) dan Station.
        $manpowerStations = ManPowerManyStation::where('status', 'Pending')
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
        
        // Periksa apakah status sudah bukan Pending (opsional)
        if ($omm->status !== 'Pending') {
             return back()->with('error', 'Status OMM sudah bukan Pending.');
        }

        $omm->update([
            'status' => 'Approved',
            'approved_by' => auth()->id(), // Asumsi Anda menggunakan sistem autentikasi
            'approved_at' => now(),
        ]);

        return redirect()->route('approval.omm.index')
            ->with('success', 'Pengajuan One Man Can Many Stations berhasil di-Approve.');
    }

    /**
     * Mengubah status pengajuan OMM menjadi 'Revisi' atau 'Rejected'.
     * Endpoint: /approval/omm/{id}/revisi
     */
    public function reviseOmm(Request $request, $id)
    {
        $request->validate([
            'revision_notes' => 'required|string|max:500',
        ]);
        
        $omm = ManPowerManyStation::findOrFail($id);

        // Periksa apakah status sudah bukan Pending (opsional)
        if ($omm->status !== 'Pending') {
             return back()->with('error', 'Status OMM sudah bukan Pending.');
        }

        $omm->update([
            'status' => 'Revision', // Atau 'Rejected'
            'revision_notes' => $request->revision_notes,
            'approved_by' => auth()->id(),
            'approved_at' => now(), // Catat waktu dan siapa yang merevisi/mereject
        ]);

        return redirect()->route('approval.omm.index')
            ->with('success', 'Pengajuan One Man Can Many Stations berhasil di-Revisi.');
    }
    
    // Fungsi updateManPowerStatus dihapus karena fungsinya sudah tercakup dalam approveOmm dan reviseOmm
    // Fungsi editManPower dihapus karena tidak relevan dengan proses approval OMM.

    // GET: Ambil station berdasarkan line_area
    public function stationsByLine(Request $request)
    {
        return Station::where('line_area', $request->line_area)->get();
    }

    // POST: Tambahkan relasi
    public function store(Request $request)
    {
        $request->validate([
            'man_power_id' => 'required|integer',
            'station_id' => 'required|integer',
                'status' => 'PENDING', 

        ]);

        $mp = ManPower::findOrFail($request->man_power_id);

        $mp->stations()->syncWithoutDetaching([$request->station_id]);

        return response()->json(['success' => true]);
    }

    // DELETE: Hapus relasi
    public function destroy($station_id, Request $request)
    {
        $request->validate([
            'man_power_id' => 'required|integer',
        ]);

        $mp = ManPower::findOrFail($request->man_power_id);

        $mp->stations()->detach($station_id);

        return response()->json(['success' => true]);
    }
}
