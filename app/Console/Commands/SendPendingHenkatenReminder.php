<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ManPowerHenkaten;
use App\Models\MethodHenkaten;
use App\Models\MaterialHenkaten;
use App\Models\MachineHenkaten;
use App\Models\User;    
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class SendPendingHenkatenReminder extends Command
{
    protected $signature = 'henkaten:reminder';
    protected $description = 'Kirim reminder email jika status Henkaten pending lebih dari 7 hari';

    public function handle()
    {
        $deadline = Carbon::now()->subDays(7);

        // Ambil per role section head
        $sectionHeads = User::whereIn('role', [
            'Sect Head QC',
            'Sect Head PPIC',
            'Sect Head Produksi'
        ])->get();

        foreach ($sectionHeads as $head) {

            // Ambil pending sesuai role (sama seperti logic index user)
            $pendingData = $this->getPendingForRole($head->role)
                ->where('created_at', '<=', $deadline)
                ->get();

            if ($pendingData->count() > 0) {

                Mail::send('email.Approval_henkaten_manpower_reminder', [
                    'name' => $head->name,
                    'role' => $head->role,
                    'total' => $pendingData->count(),
                    'items' => $pendingData
                ], function($msg) use ($head) {
                    $msg->to($head->email)
                        ->subject('Reminder Approval Henkaten Pending > 7 Hari');
                });

                $this->info("Reminder dikirim ke: {$head->name}");
            }
        }

        return Command::SUCCESS;
    }


    private function getPendingForRole($role)
    {
        $manpowers = ManPowerHenkaten::where('status', 'Pending');
        $methods = MethodHenkaten::where('status', 'Pending');
        $materials = MaterialHenkaten::where('status', 'Pending');
        $machines = MachineHenkaten::where('status', 'Pending');

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
                $allowed = ['FA L1','FA L2','FA L3','FA L5','FA L6','SMT L1','SMT L2'];
                $manpowers->whereIn('line_area', $allowed);
                $methods->whereIn('line_area', $allowed);
                $materials->whereIn('line_area', $allowed);
                $machines->whereIn('line_area', $allowed);
                break;
        }

        return $manpowers
            ->union($methods)
            ->union($materials)
            ->union($machines);
    }
}
