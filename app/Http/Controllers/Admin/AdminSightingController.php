<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sighting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSightingController extends Controller
{
              public function index()
              {
                            $sightings = Sighting::with(['user', 'plant'])
                                          ->latest()
                                          ->paginate(10);

                            return Inertia::render('Admin/Sightings/Index', [
                                          'sightings' => $sightings
                            ]);
              }

              public function destroy(Sighting $sighting)
              {
                            $sighting->delete();
                            return back()->with('success', 'Sighting deleted successfully.');
              }
}
