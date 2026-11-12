<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::all();
        return view('admin.zones.index', compact('zones'));
    }

    public function create()
    {
        return view('admin.zones.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:40',
            'prix' => 'required|integer|min:0',
            'actif' => 'boolean',
        ]);

        Zone::create($request->all());

        return redirect()->route('admin.zones.index')->with('success', 'Zone créée avec succès.');
    }

    public function edit($id)
    {
        $zone = Zone::findOrFail($id);
        return view('admin.zones.edit', compact('zone'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:40',
            'prix' => 'required|integer|min:0',
            'actif' => 'boolean',
        ]);

        $zone = Zone::findOrFail($id);
        $zone->update($request->all());

        return redirect()->route('admin.zones.index')->with('success', 'Zone mise à jour avec succès.');
    }

    public function destroy($id)
    {
        $zone = Zone::findOrFail($id);
        $zone->delete();

        return redirect()->route('admin.zones.index')->with('success', 'Zone supprimée.');
    }
}
