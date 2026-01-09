<?php

namespace App\Http\Controllers;

use App\Models\MasterDataLog;
use App\Models\ManPower;
use App\Models\Method;
use App\Models\Machine;
use App\Models\Material;
use Illuminate\Http\Request;

class MasterDataLogController extends Controller
{
    /**
     * Get role-based line area filter prefix
     * Returns null if all line areas should be shown (Admin, Sect Head FA)
     */
    private function getRoleBasedLineAreaFilter()
    {
        $user = auth()->user();
        if (!$user) return null;

        $role = $user->role ?? '';

        return match($role) {
            'Leader FA', 'SubLeader FA' => 'FA L%',
            'Leader SMT' => 'SMT L%',
            'Leader PPIC', 'Sect Head PPIC' => 'Delivery',
            'Leader QC', 'Sect Head QC' => 'Incoming',
            default => null, // Admin, Sect Head FA, etc - show all
        };
    }

    /**
     * Get distinct line areas from logs for a specific loggable type
     * Filtered by user role
     */
    private function getDistinctLineAreas($loggableType)
    {
        $query = MasterDataLog::where('loggable_type', $loggableType)
            ->whereNotNull('details')
            ->selectRaw("JSON_VALUE(details, '$.line_area') as line_area");

        // Apply role-based filter
        $roleFilter = $this->getRoleBasedLineAreaFilter();
        if ($roleFilter) {
            if (str_contains($roleFilter, '%')) {
                // LIKE pattern for FA L% or SMT L%
                $query->whereRaw("JSON_VALUE(details, '$.line_area') LIKE ?", [$roleFilter]);
            } else {
                // Exact match for Delivery, Incoming
                $query->whereRaw("JSON_VALUE(details, '$.line_area') = ?", [$roleFilter]);
            }
        }

        return $query->distinct()
            ->orderBy('line_area')
            ->pluck('line_area')
            ->filter()
            ->values();
    }

    /**
     * Apply role-based line area filter to query
     */
    private function applyRoleBasedFilter($query)
    {
        $roleFilter = $this->getRoleBasedLineAreaFilter();
        if ($roleFilter) {
            if (str_contains($roleFilter, '%')) {
                // LIKE pattern for FA L% or SMT L%
                $query->whereRaw("JSON_VALUE(details, '$.line_area') LIKE ?", [$roleFilter]);
            } else {
                // Exact match for Delivery, Incoming
                $query->whereRaw("JSON_VALUE(details, '$.line_area') = ?", [$roleFilter]);
            }
        }
        return $query;
    }

    public function manpower(Request $request)
    {
        $query = MasterDataLog::with('user')
            ->where('loggable_type', ManPower::class)
            ->latest('created_at');

        // Apply role-based filter
        $this->applyRoleBasedFilter($query);

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('line_area')) {
            $query->whereRaw("JSON_VALUE(details, '$.line_area') = ?", [$request->line_area]);
        }

        $logs = $query->paginate(15)->appends($request->query());
        $lineAreas = $this->getDistinctLineAreas(ManPower::class);

        return view('master-log.manpower', [
            'logs' => $logs,
            'created_date' => $request->created_date,
            'action' => $request->action,
            'line_area' => $request->line_area,
            'lineAreas' => $lineAreas,
        ]);
    }

    public function method(Request $request)
    {
        $query = MasterDataLog::with('user')
            ->where('loggable_type', Method::class)
            ->latest('created_at');

        // Apply role-based filter
        $this->applyRoleBasedFilter($query);

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('line_area')) {
            $query->whereRaw("JSON_VALUE(details, '$.line_area') = ?", [$request->line_area]);
        }

        $logs = $query->paginate(15)->appends($request->query());
        $lineAreas = $this->getDistinctLineAreas(Method::class);

        return view('master-log.method', [
            'logs' => $logs,
            'created_date' => $request->created_date,
            'action' => $request->action,
            'line_area' => $request->line_area,
            'lineAreas' => $lineAreas,
        ]);
    }

    public function machine(Request $request)
    {
        $query = MasterDataLog::with('user')
            ->where('loggable_type', Machine::class)
            ->latest('created_at');

        // Apply role-based filter
        $this->applyRoleBasedFilter($query);

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('line_area')) {
            $query->whereRaw("JSON_VALUE(details, '$.line_area') = ?", [$request->line_area]);
        }

        $logs = $query->paginate(15)->appends($request->query());
        $lineAreas = $this->getDistinctLineAreas(Machine::class);

        return view('master-log.machine', [
            'logs' => $logs,
            'created_date' => $request->created_date,
            'action' => $request->action,
            'line_area' => $request->line_area,
            'lineAreas' => $lineAreas,
        ]);
    }

    public function material(Request $request)
    {
        $query = MasterDataLog::with('user')
            ->where('loggable_type', Material::class)
            ->latest('created_at');

        // Apply role-based filter
        $this->applyRoleBasedFilter($query);

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('line_area')) {
            $query->whereRaw("JSON_VALUE(details, '$.line_area') = ?", [$request->line_area]);
        }

        $logs = $query->paginate(15)->appends($request->query());
        $lineAreas = $this->getDistinctLineAreas(Material::class);

        return view('master-log.material', [
            'logs' => $logs,
            'created_date' => $request->created_date,
            'action' => $request->action,
            'line_area' => $request->line_area,
            'lineAreas' => $lineAreas,
        ]);
    }


    public function destroy($id)
    {
        $log = MasterDataLog::findOrFail($id);
        $log->delete();

        return back()->with('success', 'Data log berhasil dihapus.');
    }
}
