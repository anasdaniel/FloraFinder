<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPlantController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Plants/Index', [
            'plants' => Plant::latest()->paginate(10),
        ]);
    }

    public function edit(Plant $plant)
    {
        return Inertia::render('Admin/Plants/Edit', [
            'plant' => $plant,
        ]);
    }

    public function update(Request $request, Plant $plant)
    {
        $validated = $request->validate([
            'common_name' => 'required|string|max:255',
            'scientific_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'care_level' => 'nullable|string',
            'watering' => 'nullable|string',
            'sunlight' => 'nullable|string',
        ]);

        $plant->update($validated);

        return redirect()->route('admin.plants.index')->with('success', 'Plant updated successfully.');
    }

    public function destroy(Plant $plant)
    {
        $plant->delete();

        return back()->with('success', 'Plant deleted successfully.');
    }
}
