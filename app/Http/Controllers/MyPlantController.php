<?php

namespace App\Http\Controllers;

use App\Models\PlantIdentification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MyPlantController extends Controller
{
    /**
     * Display the user's plant collection.
     */
    public function index(Request $request)
    {
        $query = PlantIdentification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('scientific_name', 'like', "%{$search}%")
                    ->orWhere('common_name', 'like', "%{$search}%")
                    ->orWhere('family', 'like', "%{$search}%")
                    ->orWhere('genus', 'like', "%{$search}%");
            });
        }

        // Family filter
        if ($family = $request->input('family')) {
            $query->where('family', $family);
        }

        $plants = $query->paginate(12)->withQueryString();

        // Get unique families for filter dropdown
        $families = PlantIdentification::where('user_id', Auth::id())
            ->whereNotNull('family')
            ->distinct()
            ->orderBy('family')
            ->pluck('family')
            ->toArray();

        return Inertia::render('MyPlants/Index', [
            'plants' => $plants,
            'families' => $families,
            'filters' => [
                'search' => $request->input('search'),
                'family' => $request->input('family'),
            ],
        ]);
    }

    /**
     * Display a specific plant from the user's collection.
     */
    public function show(PlantIdentification $plant)
    {
        // Ensure the plant belongs to the current user
        if ($plant->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('MyPlants/Show', [
            'plant' => $plant,
        ]);
    }

    /**
     * Remove a plant from the user's collection.
     */
    public function destroy(PlantIdentification $plant)
    {
        // Ensure the plant belongs to the current user
        if ($plant->user_id !== Auth::id()) {
            abort(403);
        }

        // Delete the associated image file if it exists
        if ($plant->path && Storage::disk('public')->exists($plant->path)) {
            Storage::disk('public')->delete($plant->path);
        }

        $plant->delete();

        return redirect()->back()->with('success', 'Plant removed from your collection.');
    }
}