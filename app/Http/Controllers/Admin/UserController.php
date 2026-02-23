<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UserExport;
use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Circulation;
use App\Models\Type;
use App\Models\User;
use App\Models\UserCategory;
use Illuminate\Http\Request;

use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:user view', only: ['index', 'show']),
            new Middleware('permission:user create', only: ['create', 'store']),
            new Middleware('permission:user update', only: ['edit', 'update', 'recover']),
            new Middleware('permission:user delete', only: ['destroy', 'destroy_image']),
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
        $status = $request->input('status');
        $role = $request->input('role');       // filter by role
        $trashed = $request->input('trashed'); // '', 'with', 'only'

        $query = User::query();

        $query->with('created_user', 'updated_user', 'roles', 'title');

        // if ($status) {
        //     $query->where('status', $status);
        // }

        // Filter by Spatie role
        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Filter by trashed (soft deletes)
        if ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === 'only') {
            $query->onlyTrashed();
        }

        $query->orderBy($sortBy, $sortDirection);

        if ($search) {
            $query->where(function ($sub_query) use ($search) {
                return $sub_query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('name_kh', 'LIKE', "%{$search}%")
                    ->orWhere('card_number', 'LIKE', "%{$search}%")
                    ->orWhere('id', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy('id', 'desc');
        $query->withCount('author_items', 'publisher_items', 'advisor_items', 'posts');

        $tableData = $query->paginate($perPage)->onEachSide(1);

        return Inertia::render('Admin/Users/Index', [
            'tableData' => $tableData,
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Create', [
            'types' => Type::where('group_code', 'user-title-type-group')->orderBy('order_index')->orderBy('name')->get(),
            'userCategories' => UserCategory::orderBy('order_index')->orderBy('name')->get(),
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_kh' => 'nullable|string|max:255',
            'card_number' => 'nullable|string|max:255|unique:users,card_number',
            'expired_at' => 'nullable|date|date_format:Y-m-d|after_or_equal:today',
            'title_type_code' => 'nullable|string|max:255|exists:types,code',
            'category_code' => 'nullable|string|max:255|exists:user_categories,code',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|max:255|confirmed', // Laravel auto-validates against confirm_password
            'phone' => 'nullable|numeric|digits_between:8,15|unique:users,phone',
            'gender' => 'nullable|string|in:male,female,other',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg,webp|max:4096',
            'roles' => 'required|array'
        ]);
        // dd($validated );
        $userCategory = UserCategory::where('code', $request->category_code)->first();

        if (empty($validated['expired_at']) && $userCategory && $userCategory->enrollment_period_months) {
            $validated['expired_at'] = now()
                ->addMonths($userCategory->enrollment_period_months)
                ->format('Y-m-d');
        }

        try {
            // Add creator and updater
            $validated['created_by'] = $request->user()->id;
            $validated['updated_by'] = $request->user()->id;

            // Hash the password
            $validated['password'] = Hash::make($validated['password']);

            // Extract and unset non-model fields
            $roles = $request->input('roles', []);
            $imageFile = $request->file('image');
            unset($validated['image']);

            // Handle image upload if present
            if ($imageFile) {
                $imageName = ImageHelper::uploadAndResizeImageWebp($imageFile, 'assets/images/users', 600);
                $validated['image'] = $imageName;
            }

            // Create the user
            $user = User::create($validated);

            // Assign roles
            if (!empty($roles)) {
                $user->syncRoles($roles);
            } else {
                $user->syncRoles('User');
            }

            return redirect()->back()->with('success', 'User create successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Failed to create user: ' . $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $userData = $user->load('roles');
        $userCirculations = Circulation::where('borrower_id', $user->id)
            ->orderByDesc('borrowed_at')
            ->with('item_physical_copy:id,item_id,barcode,item_type_code,home_library_code,current_library_code', 'item_physical_copy.item:id,name', 'item_physical_copy.item_type:id,code,name,name_kh')
            ->get();
        // return $userCirculations;
        return Inertia::render('Admin/Users/Show', [
            'userData' => $userData,
            'userCirculations' => $userCirculations,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Create', [
            'editData' => $user->load('roles'),
            'types' => Type::where('group_code', 'user-title-type-group')->orderBy('order_index')->orderBy('name')->get(),
            'userCategories' => UserCategory::orderBy('order_index')->orderBy('name')->get(),
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_kh' => 'nullable|string|max:255',
            'card_number' => 'nullable|string|max:255|unique:users,card_number,'  . $user->id,
            'expired_at' => 'nullable|date|date_format:Y-m-d',
            'title_type_code' => 'nullable|string|max:255|exists:types,code',
            'category_code' => 'nullable|string|max:255|exists:user_categories,code',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|max:255|confirmed', // Laravel auto-validates against confirm_password
            'phone' => 'nullable|numeric|digits_between:8,15|unique:users,phone,' . $user->id,
            'gender' => 'nullable|string|in:male,female,other',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg,webp|max:4096',
            'roles' => 'required|array'
        ]);

        try {
            // Add updater
            $validated['updated_by'] = $request->user()->id;

            // Hash the password
            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            // Extract and unset non-model fields
            $roles = $request->input('roles', []);
            $imageFile = $request->file('image');
            unset($validated['image']);

            // Handle image upload if present
            if ($imageFile) {
                $imageName = ImageHelper::uploadAndResizeImageWebp($imageFile, 'assets/images/users', 600);
                $validated['image'] = $imageName;
                if ($imageName && $user->image) {
                    ImageHelper::deleteImage($user->image, 'assets/images/users');
                }
            }

            // Create the user
            $user->update($validated);

            // Assign roles
            if (!empty($roles)) {
                $user->syncRoles($roles);
            } else {
                $user->syncRoles('User');
            }

            return redirect()->back()->with('success', 'User created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Failed to create user: ' . $e->getMessage());
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function recover($id)
    {
        $user = User::withTrashed()->findOrFail($id); // 👈 include soft-deleted users
        $user->restore(); // restores deleted_at to null
        return redirect()->back()->with('success', 'User recovered successfully.');
    }


    public function destroy(User $user)
    {
        // if ($user->image) {
        //     ImageHelper::deleteImage($user->image, 'assets/images/users');
        // }

        $user->delete(); // this will now just set deleted_at timestamp
        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    // EXPORT
    public function export_users(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy', 'id');
        $sortDirection = $request->input('sortDirection', 'desc');
        $role = $request->input('role');
        $trashed = $request->input('trashed');

        $query = User::query()->with('created_user', 'updated_user', 'roles', 'title');

        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        if ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === 'only') {
            $query->onlyTrashed();
        }

        if ($search) {
            $query->where(function ($sub_query) use ($search) {
                $sub_query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('name_kh', 'LIKE', "%{$search}%")
                    ->orWhere('card_number', 'LIKE', "%{$search}%")
                    ->orWhere('id', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $rows = $query->orderBy($sortBy, $sortDirection)->get();

        return Excel::download(new UserExport($rows), 'users.xlsx');
    }
}
