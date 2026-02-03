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
use Illuminate\Support\Facades\Log;

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

        $this->info("Found " . $sectionHeads->count() . " Section Heads");

        foreach ($sectionHeads as $head) {
            $this->info("Processing: {$head->name} ({$head->role})");

            // Kumpulkan pending data per tipe
            $allPendingItems = collect();
            $summary = [];

            // ManPower
            $manpowerQuery = $this->applyRoleFilter(ManPowerHenkaten::query(), $head->role);
            $manpowerPending = $manpowerQuery
                ->where('status', 'Pending')
                ->where('created_at', '<=', $deadline)
                ->get();

            if ($manpowerPending->count() > 0) {
                $summary['Man Power'] = $manpowerPending->count();
                foreach ($manpowerPending as $item) {
                    $allPendingItems->push([
                        'type' => 'Man Power',
                        'npk' => $item->nama ?? '-',
                        'line_area' => $item->line_area,
                        'created_at' => $item->created_at,
                        'keterangan' => $item->keterangan ?? '-',
                    ]);
                }
            }

            // Method
            $methodQuery = $this->applyRoleFilter(MethodHenkaten::query(), $head->role);
            $methodPending = $methodQuery
                ->where('status', 'Pending')
                ->where('created_at', '<=', $deadline)
                ->get();

            if ($methodPending->count() > 0) {
                $summary['Method'] = $methodPending->count();
                foreach ($methodPending as $item) {
                    $allPendingItems->push([
                        'type' => 'Method',
                        'npk' => '-',
                        'line_area' => $item->line_area,
                        'created_at' => $item->created_at,
                        'keterangan' => $item->keterangan ?? '-',
                    ]);
                }
            }

            // Material
            $materialQuery = $this->applyRoleFilter(MaterialHenkaten::query(), $head->role);
            $materialPending = $materialQuery
                ->where('status', 'Pending')
                ->where('created_at', '<=', $deadline)
                ->get();

            if ($materialPending->count() > 0) {
                $summary['Material'] = $materialPending->count();
                foreach ($materialPending as $item) {
                    $allPendingItems->push([
                        'type' => 'Material',
                        'npk' => '-',
                        'line_area' => $item->line_area,
                        'created_at' => $item->created_at,
                        'keterangan' => $item->keterangan ?? '-',
                    ]);
                }
            }

            // Machine
            $machineQuery = $this->applyRoleFilter(MachineHenkaten::query(), $head->role);
            $machinePending = $machineQuery
                ->where('status', 'Pending')
                ->where('created_at', '<=', $deadline)
                ->get();

            if ($machinePending->count() > 0) {
                $summary['Machine'] = $machinePending->count();
                foreach ($machinePending as $item) {
                    $allPendingItems->push([
                        'type' => 'Machine',
                        'npk' => '-',
                        'line_area' => $item->line_area,
                        'created_at' => $item->created_at,
                        'keterangan' => $item->keterangan ?? '-',
                    ]);
                }
            }

            // Jika ada pending items, kirim email
            if ($allPendingItems->count() > 0) {
                $totalCount = $allPendingItems->count();
                try {
                    Mail::send('email.henkaten_reminder', [
                        'name' => $head->name,
                        'role' => $head->role,
                        'total' => $totalCount,
                        'items' => $allPendingItems,
                        'summary' => $summary,
                    ], function ($msg) use ($head, $totalCount) {
                        $msg->to($head->email)
                            ->subject('Reminder: ' . $totalCount . ' Henkaten Pending > 7 Hari');
                    });

                    $this->info("  ✓ Email sent to {$head->email} ({$totalCount} items)");
                    Log::info("Henkaten reminder sent to {$head->email}", ['count' => $totalCount]);
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to send email: " . $e->getMessage());
                    Log::error("Failed to send henkaten reminder to {$head->email}: " . $e->getMessage());
                }
            } else {
                $this->info("  - No pending items > 7 days");
            }
        }

        $this->info("Done!");
        return Command::SUCCESS;
    }

    /**
     * Apply line_area filter based on role
     */
    private function applyRoleFilter($query, $role)
    {
        switch ($role) {
            case 'Sect Head QC':
                return $query->whereRaw("LOWER(line_area) LIKE 'incoming%'");

            case 'Sect Head PPIC':
                return $query->where('line_area', 'Delivery');

            case 'Sect Head Produksi':
                $allowed = ['FA L1', 'FA L2', 'FA L3', 'FA L5', 'FA L6', 'SMT L1', 'SMT L2'];
                return $query->whereIn('line_area', $allowed);

            default:
                return $query;
        }
    }
}
