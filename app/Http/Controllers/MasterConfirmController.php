<?php


namespace App\Http\Controllers;

use App\Models\ManPower;
use App\Models\Method;
use App\Models\Machine;
use App\Models\Material;
use Illuminate\Http\Request;
use App\Models\Role;



class MasterConfirmController extends Controller
{
    public function index()
    {
        $manpowers = ManPower::where('status', 'Pending')->get();
        $methods   = Method::where('status', 'Pending')->get();
        $machines  = Machine::where('status', 'Pending')->get();
        $materials = Material::where('status', 'Pending')->get();

        return view('secthead.master-confirm', compact('manpowers', 'methods', 'machines', 'materials'));
    }

    public function approve($type, $id)
    {
        $model = $this->getModel($type)::findOrFail($id);
        $model->status = 'Approved';
        $model->save();

        return back()->with('success', ucfirst($type).' disetujui.');
    }

    public function revisi($type, $id)
    {
        $model = $this->getModel($type)::findOrFail($id);
        $model->status = 'Revisi';
        $model->save();

        return back()->with('warning', ucfirst($type).' dikembalikan untuk revisi.');
    }

  private function getModel($type)
{
    return match($type) {
        'manpower' => ManPower::class,
        'method'   => Method::class,
        'machine'  => Machine::class,
        'material' => Material::class,
        'role'     => Role::class, 
        default    => abort(404),
    };
}

    public function show($type, $id)
{
    $model = $this->getModel($type)::findOrFail($id);
    return response()->json($model);
}

}
