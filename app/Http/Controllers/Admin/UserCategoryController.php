<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserCategory;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class UserCategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:user_category view', only: ['index', 'show']),
            new Middleware('permission:user_category create', only: ['create', 'store']),
            new Middleware('permission:user_category update', only: ['edit', 'update', 'recover']),
            new Middleware('permission:user_category delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy', 'id');
        $sortDirection = $request->input('sortDirection', 'desc');
        $type_code = $request->input('user_category_type_code');
        $trashed = $request->input('trashed');

        $query = UserCategory::query();

        if ($type_code) {
            $query->where('user_category_type_code', $type_code);
        }

        if ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === 'only') {
            $query->onlyTrashed();
        }

        if ($search) {
            $query->where(function ($sub_query) use ($search) {
                return $sub_query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('name_kh', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy($sortBy, $sortDirection);
        $query->with('created_user', 'updated_user');

        $tableData = $query->paginate($perPage)->onEachSide(1);

        return Inertia::render('Admin/UserCategory/Index', [
            'tableData' => $tableData,
            // Assuming your User Types are in the 'types' table under a specific group
            'types' => Type::where('group_code', 'user-category-type-group')->orderBy('order_index')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/UserCategory/Create', [
            'types' => Type::where('group_code', 'user-category-type-group')->orderBy('order_index')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:user_categories,code',
            'name' => 'required|string|max:255',
            'name_kh' => 'nullable|string|max:255',
            'order_index' => 'required|integer',
            'user_category_type_code' => 'nullable|string|exists:types,code',
            'enrollment_period_months' => 'required|integer|min:0',
            'enrollment_fee' => 'required|numeric|min:0',

            'fine_amount_per_day' => 'nullable|numeric|min:0|max:999999.99',
            'max_fines_amount' => 'nullable|numeric|min:0|max:999999.99',
            'borrowing_limit' => 'nullable|integer|min:0|max:1000',
            'loan_period' => 'nullable|integer|min:1|max:365', // Limit to a year max for safety
        ]);

        try {
            $validated['created_by'] = $request->user()->id;
            $validated['updated_by'] = $request->user()->id;

            UserCategory::create($validated);

            return redirect()->back()->with('success', 'User Category created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Failed to create Category: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UserCategory $userCategory)
    {
        return Inertia::render('Admin/UserCategory/Create', [
            'editData' => $userCategory,
            'readOnly' => true,
            'types' => Type::where('group_code', 'user-category-type-group')->orderBy('order_index')->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserCategory $userCategory)
    {
        return Inertia::render('Admin/UserCategory/Create', [
            'editData' => $userCategory,
            'types' => Type::where('group_code', 'user-category-type-group')->orderBy('order_index')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserCategory $userCategory)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:user_categories,code,' . $userCategory->id,
            'name' => 'required|string|max:255',
            'name_kh' => 'nullable|string|max:255',
            'order_index' => 'required|integer',
            'user_category_type_code' => 'nullable|string|exists:types,code',
            'enrollment_period_months' => 'required|integer|min:0',
            'enrollment_fee' => 'required|numeric|min:0',

            'fine_amount_per_day' => 'nullable|numeric|min:0|max:999999.99',
            'max_fines_amount' => 'nullable|numeric|min:0|max:999999.99',
            'borrowing_limit' => 'nullable|integer|min:0|max:1000',
            'loan_period' => 'nullable|integer|min:1|max:365', // Limit to a year max for safety
        ]);

        try {
            $validated['updated_by'] = $request->user()->id;

            $userCategory->update($validated);

            return redirect()->back()->with('success', 'User Category updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Failed to update Category: ' . $e->getMessage());
        }
    }

    public function recover($id)
    {
        $category = UserCategory::withTrashed()->findOrFail($id);
        $category->restore();
        return redirect()->back()->with('success', 'Category recovered successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserCategory $userCategory)
    {
        $userCategory->delete();
        return redirect()->back()->with('success', 'Category deleted successfully.');
    }
}
