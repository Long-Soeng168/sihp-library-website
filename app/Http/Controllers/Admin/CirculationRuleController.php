<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\CirculationRule;
use Illuminate\Http\Request;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class CirculationRuleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:circulation view', only: ['index', 'show']),
            new Middleware('permission:circulation create', only: ['create', 'store']),
            new Middleware('permission:circulation update', only: ['edit', 'update', 'recover']),
            new Middleware('permission:circulation delete', only: ['destroy', 'destroy_image']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $circulation_rule = CirculationRule::first();
        if ($circulation_rule) {
            return redirect("/admin/circulation-rules/{$circulation_rule->id}/edit");
        } else {
            return redirect("/admin/circulation-rules/create");
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/CirculationRule/Create',);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fine_amount_per_day' => 'required|numeric|min:0|max:999999.99',
            'max_fines_amount' => 'required|numeric|min:0|max:999999.99',
            'borrowing_limit' => 'required|integer|min:0|max:1000',
            'loan_period' => 'required|integer|min:1|max:365', // Limit to a year max for safety
        ]);

        try {
            $circulation_rule = CirculationRule::create($validated);

            return redirect("/admin/circulation-rules/{$circulation_rule->id}/edit")->with('success', 'Circulation Rule created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Failed to create Circulation Rule: ' . $e->getMessage());
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(CirculationRule $circulation_rule)
    {
        return Inertia::render('Admin/CirculationRule/Create', [
            'editData' => $circulation_rule,
            'readOnly' => true,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CirculationRule $circulation_rule)
    {
        return Inertia::render('Admin/CirculationRule/Create', [
            'editData' => $circulation_rule,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CirculationRule $circulation_rule)
    {
        $validated = $request->validate([
            'fine_amount_per_day' => 'required|numeric|min:0|max:999999.99',
            'max_fines_amount' => 'required|numeric|min:0|max:999999.99',
            'borrowing_limit' => 'required|integer|min:0|max:1000',
            'loan_period' => 'required|integer|min:1|max:365', // Limit to a year max for safety
        ]);

        try {
            // separate file handling for logos
            $logoFile = $request->file('logo');
            $darkLogoFile = $request->file('logo_darkmode');

            unset($validated['logo'], $validated['logo_darkmode']);

            // Handle normal logo
            if ($logoFile) {
                $logoName = ImageHelper::uploadAndResizeImageWebp(
                    $logoFile,
                    'assets/images/circulation_rules',
                    600
                );

                // delete old logo if exists
                if ($circulation_rule->logo) {
                    ImageHelper::deleteImage($circulation_rule->logo, 'assets/images/circulation_rules');
                }

                $validated['logo'] = $logoName;
            }

            // Handle dark mode logo
            if ($darkLogoFile) {
                $darkLogoName = ImageHelper::uploadAndResizeImageWebp(
                    $darkLogoFile,
                    'assets/images/circulation_rules',
                    600
                );

                // delete old dark logo if exists
                if ($circulation_rule->logo_darkmode) {
                    ImageHelper::deleteImage($circulation_rule->logo_darkmode, 'assets/images/circulation_rules');
                }

                $validated['logo_darkmode'] = $darkLogoName;
            }

            // Update the Circulation Rule
            $circulation_rule->update($validated);

            return redirect()->back()->with('success', 'Circulation Rule updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Failed to update Circulation Rule: ' . $e->getMessage());
        }
    }



    public function recover($id)
    {
        $circulation_rule = CirculationRule::withTrashed()->findOrFail($id); // 👈 include soft-deleted Circulation Rule
        $circulation_rule->restore(); // restores deleted_at to null
        return redirect()->back()->with('success', 'Circulation Rule recovered successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CirculationRule $circulation_rule)
    {
        // if ($user->image) {
        //     ImageHelper::deleteImage($user->image, 'assets/images/users');
        // }

        $circulation_rule->delete(); // this will now just set deleted_at timestamp
        return redirect()->back()->with('success', 'Circulation Rule deleted successfully.');
    }
}
