<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ManPowerHenkaten;
use App\Models\MethodHenkaten;
use App\Models\MaterialHenkaten;
use App\Models\MachineHenkaten;
use App\Models\ManPower;
use App\Models\ManPowerManyStation;

class HenkatenApprovalController extends Controller
{
    /**
     * Menampilkan list Henkaten sesuai role dan line_area.
     */
    public function index()
    {
        $user = Auth::user();
        $role = $user->role;

        $manpowers = ManPowerHenkaten::where('status', 'Pending');
        $methods = MethodHenkaten::where('status', 'Pending');
        $materials = MaterialHenkaten::where('status', 'Pending');
        $machines = MachineHenkaten::where('status', 'Pending');

        // Filter line area sesuai role
        switch ($role) {
            case 'Sect Head QC':
                $manpowers->whereRaw("LOWER(line_area) LIKE 'incoming%'");
                $methods->whereRaw("LOWER(line_area) LIKE 'incoming%'");
                $materials->whereRaw("LOWER(line_area) LIKE 'incoming%'");
                $machines->whereRaw("LOWER(line_area) LIKE 'incoming%'");
                break;

            case 'Sect Head PPIC':
                $manpowers->where('line_area', 'Delivery');
                $methods->where('line_area', 'Delivery');
                $materials->where('line_area', 'Delivery');
                $machines->where('line_area', 'Delivery');
                break;

            case 'Sect Head Produksi':
                $allowedLineAreas = [
                    'FA L1','FA L2','FA L3','FA L5','FA L6',
                    'SMT L1','SMT L2'
                ];

                $manpowers->whereIn('line_area', $allowedLineAreas);
                $methods->whereIn('line_area', $allowedLineAreas);
                $materials->whereIn('line_area', $allowedLineAreas);
                $machines->whereIn('line_area', $allowedLineAreas);
                break;

            default:
                return redirect()->back()->with('error', 'Role tidak dikenali.');
        }

        return view('secthead.henkaten-approval', [
            'manpowers' => $manpowers->get(),
            'methods' => $methods->get(),
            'materials' => $materials->get(),
            'machines' => $machines->get(),
        ]);
    }

    /**
     * Ambil instance Henkaten berdasarkan tipe dan ID.
     */
    private function getHenkatenItem($type, $id)
    {
        return match($type) {
            'manpower' => ManPowerHenkaten::find($id),
            'method' => MethodHenkaten::find($id),
            'material' => MaterialHenkaten::find($id),
            'machine' => MachineHenkaten::find($id),
            default => null,
        };
    }

    /**
     * Approve Henkaten
     */
    public function approveHenkaten(Request $request, $type, $id)
    {
        $user = Auth::user();
        $allowedLineAreas = [
            'FA L1','FA L2','FA L3','FA L5','FA L6',
            'SMT L1','SMT L2'
        ];

        $item = $this->getHenkatenItem($type, $id);

        if (!$item) {
            return redirect()->route('henkaten.approval.index')->with('error', 'Data Henkaten tidak ditemukan.');
        }

        // Validasi akses Sect Head Produksi
        if ($user->role === 'Sect Head Produksi' && !in_array($item->line_area, $allowedLineAreas)) {
            return redirect()->route('henkaten.approval.index')->with('error', 'Anda tidak memiliki akses untuk approve Henkaten di line ini.');
        }

        try {
            DB::beginTransaction();

            $statusToSet = 'Approved';

            // Logika khusus untuk manpower PERMANEN
            if ($type == 'manpower' && $item->note == 'PERMANEN') {
                $masterManPower = ManPower::find($item->man_power_id);

                if ($masterManPower) {
                    $masterManPower->nama = $item->nama_after;
                    $masterManPower->save();
                } else {
                    throw new \Exception('Data Master ManPower (ID: ' . $item->man_power_id . ') tidak ditemukan. Approval dibatalkan.');
                }

                $statusToSet = 'APPROVED';
            }

            $item->status = $statusToSet;
            $item->save();

            DB::commit();

            return redirect()->route('henkaten.approval.index')->with('success', 'Henkaten ' . ucfirst($type) . ' berhasil di-approve.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal approve Henkaten (ID: '.$item->id.', Tipe: '.$type.'): ' . $e->getMessage());
            return redirect()->route('henkaten.approval.index')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Revisi Henkaten
     */
    public function revisiHenkaten(Request $request, $type, $id)
    {
        $user = Auth::user();
        $allowedLineAreas = [
            'FA L1','FA L2','FA L3','FA L5','FA L6',
            'SMT L1','SMT L2'
        ];

        $item = $this->getHenkatenItem($type, $id);

        if (!$item) {
            return redirect()->route('henkaten.approval.index')->with('error', 'Data Henkaten tidak ditemukan.');
        }

        // Validasi akses Sect Head Produksi
        if ($user->role === 'Sect Head Produksi' && !in_array($item->line_area, $allowedLineAreas)) {
            return redirect()->route('henkaten.approval.index')->with('error', 'Anda tidak memiliki akses untuk merevisi Henkaten di line ini.');
        }

        $catatanRevisi = $request->input('revision_notes');
        $item->note = $catatanRevisi;
        $item->status = 'Revisi';
        $item->save();

        return redirect()->route('henkaten.approval.index')->with('success', 'Henkaten ' . ucfirst($type) . ' dikirim kembali untuk revisi.');
    }


   public function editManPower($id)
{
    // Pastikan relasi stations pakai nama pivot man_power_many_stations di model ManPower
    $mp = ManPower::with(['stations' => function ($q) {
        $q->select('stations.id', 'station_name', 'line_area');
    }])->find($id);

    // Jika ingin mencegah error saat mp tidak ditemukan, Anda bisa gunakan findOrFail()
    if (!$mp) {
        return redirect()->route('henkaten.approval.manpower.index')
            ->with('error', 'Manpower tidak ditemukan.');
    }

    $pivotData = DB::table('man_power_many_stations')
                    ->where('man_power_id', $id)
                    ->get();

    return view('secthead.edit-approval', compact('mp', 'pivotData'));
}



}
