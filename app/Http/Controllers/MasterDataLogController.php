<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ManPower;
use App\Models\Method;
use App\Models\Machine;
use App\Models\Material;
use Illuminate\Http\Request;

class MasterDataLogController extends Controller
{
    public function manpower(Request $request)
    {
        $query = ActivityLog::with('user')
            ->where('loggable_type', ManPower::class)
            ->latest('created_at');

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(15)->appends($request->query());

        return view('master-log.manpower', [
            'logs' => $logs,
            'created_date' => $request->created_date,
            'action' => $request->action,
        ]);
    }

    public function method(Request $request)
    {
        $query = ActivityLog::with('user')
            ->where('loggable_type', Method::class)
            ->latest('created_at');

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(15)->appends($request->query());

        return view('master-log.method', [
            'logs' => $logs,
            'created_date' => $request->created_date,
            'action' => $request->action,
        ]);
    }

    public function machine(Request $request)
    {
        $query = ActivityLog::with('user')
            ->where('loggable_type', Machine::class)
            ->latest('created_at');

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(15)->appends($request->query());

        return view('master-log.machine', [
            'logs' => $logs,
            'created_date' => $request->created_date,
            'action' => $request->action,
        ]);
    }

    public function material(Request $request)
    {
        $query = ActivityLog::with('user')
            ->where('loggable_type', Material::class)
            ->latest('created_at');

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(15)->appends($request->query());

        return view('master-log.material', [
            'logs' => $logs,
            'created_date' => $request->created_date,
            'action' => $request->action,
        ]);
    }

    /**
     * Delete activity log record
     */
    public function destroy($id)
    {
        $log = ActivityLog::findOrFail($id);
        $log->delete();

        return back()->with('success', 'Data log berhasil dihapus.');
    }
}
