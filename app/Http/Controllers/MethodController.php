<?php

namespace App\Http\Controllers;

use App\Models\Method;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
{
    $query = Method::with('station')->orderBy('id', 'desc');

    // Filter line_area
    $selectedLineArea = $request->get('line_area');
    $search = $request->get('search');

        // Ambil list line_area unik dari tabel stations
        if (auth()->check()) {
            $role = auth()->user()->role;
            if ($role === 'Leader SMT') {
                $lineAreas = Station::select('line_area')
                    ->where('line_area', 'like', 'SMT%')
                    ->distinct()
                    ->orderBy('line_area', 'asc')
                    ->pluck('line_area');
            } elseif ($role === 'Leader QC') {
                $lineAreas = collect(['Incoming']);
            } elseif ($role === 'Leader PPIC') {
                $lineAreas = collect(['Delivery']);
            } elseif ($role === 'Leader FA') {
                $lineAreas = Station::select('line_area')
                    ->where('line_area', 'like', 'FA L%')
                    ->distinct()
                    ->orderBy('line_area', 'asc')
                    ->pluck('line_area');
            } else {
                $lineAreas = Station::select('line_area')
                    ->whereNotNull('line_area')
                    ->distinct()
                    ->orderBy('line_area', 'asc')
                    ->pluck('line_area');
            }
        } else {
            $lineAreas = Station::select('line_area')
                ->whereNotNull('line_area')
                ->distinct()
                ->orderBy('line_area', 'asc')
                ->pluck('line_area');
        }

    // Search
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('keterangan', 'like', '%' . $search . '%')
              ->orWhereHas('station', function ($qs) use ($search) {
                  $qs->where('station_name', 'like', '%' . $search . '%')
                    ->orWhere('station_code', 'like', '%' . $search . '%');
              });
        });
    }

    // Jika filter dipilih
        if (auth()->check()) {
            $role = auth()->user()->role;
            if ($role === 'Leader SMT') {
                if ($selectedLineArea) {
                    $query->whereHas('station', function ($q) use ($selectedLineArea) {
                        $q->where('line_area', $selectedLineArea);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'like', 'SMT%');
                    });
                }
            } elseif ($role === 'Leader QC') {
                if ($selectedLineArea) {
                    $query->whereHas('station', function ($q) use ($selectedLineArea) {
                        $q->where('line_area', $selectedLineArea);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'Incoming');
                    });
                }
            } elseif ($role === 'Leader PPIC') {
                if ($selectedLineArea) {
                    $query->whereHas('station', function ($q) use ($selectedLineArea) {
                        $q->where('line_area', $selectedLineArea);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'Delivery');
                    });
                }
            } elseif ($role === 'Leader FA') {
                if ($selectedLineArea) {
                    $query->whereHas('station', function ($q) use ($selectedLineArea) {
                        $q->where('line_area', $selectedLineArea);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'like', 'FA L%');
                    });
                }
            } elseif ($role === 'Leader FA') {
                if ($selectedLineArea) {
                    $query->whereHas('station', function ($q) use ($selectedLineArea) {
                        $q->where('line_area', $selectedLineArea);
                    });
                } else {
                    $query->whereHas('station', function ($q) {
                        $q->where('line_area', 'like', 'FA L%');
                    });
                }
            } elseif ($selectedLineArea) {
                $query->whereHas('station', function ($q) use ($selectedLineArea) {
                    $q->where('line_area', $selectedLineArea);
                });
            }
        } elseif ($selectedLineArea) {
            $query->whereHas('station', function ($q) use ($selectedLineArea) {
                $q->where('line_area', $selectedLineArea);
            });
        }

    // Pagination
    $methods = $query->paginate(5)->appends(['line_area' => $selectedLineArea, 'search' => $search]);

    return view('methods.index', compact('methods', 'lineAreas', 'selectedLineArea', 'search'));
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stations = Station::orderBy('station_name')->get();
        return view('methods.create', compact('stations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'station_id' => 'required|exists:stations,id',
            'keterangan' => 'required|string',
            'foto_path'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('foto_path')) {
            $validated['foto_path'] = $request->file('foto_path')->store('method_photos', 'public');
        }

        Method::create($validated);

        return redirect()->route('methods.index')->with('success', 'Data method berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Method $method)
    {
        $stations = Station::orderBy('station_name')->get();
        return view('methods.edit', compact('method', 'stations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Method $method)
    {
        $validated = $request->validate([
            'station_id' => 'required|exists:stations,id',
            'keterangan' => 'required|string',
            'foto_path'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('foto_path')) {
            // Hapus foto lama jika ada
            if ($method->foto_path) {
                Storage::disk('public')->delete($method->foto_path);
            }
            $validated['foto_path'] = $request->file('foto_path')->store('method_photos', 'public');
        }

        $method->update($validated);

        return redirect()->route('methods.index')->with('success', 'Data method berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Method $method)
    {
        // Hapus foto dari storage jika ada
        if ($method->foto_path) {
            Storage::disk('public')->delete($method->foto_path);
        }

        $method->delete();

        return redirect()->route('methods.index')->with('success', 'Data method berhasil dihapus.');
    }
}
