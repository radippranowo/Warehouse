<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Role/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $allPermissions = Permission::orderBy('module')
            ->orderBy('name')
            ->get();
        
        $permissions = $allPermissions->map(function ($permission) {
            $humanName = Permission::humanReadableName($permission->name);
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $humanName,
                'module' => $permission->module,
                'description' => $permission->description,
            ];
        })->groupBy('module');

        return Inertia::render('Role/Create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles', 'alpha_dash'],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ], $this->validationMessages(), $this->validationAttributes());

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('role.index')
            ->with('success', 'Role berhasil ditambahkan');
    }

    /**
     * Show the form for editing the role.
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        
        $allPermissions = Permission::orderBy('module')
            ->orderBy('name')
            ->get();
        
        $permissions = $allPermissions->map(function ($permission) {
            $humanName = Permission::humanReadableName($permission->name);
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $humanName,
                'module' => $permission->module,
                'description' => $permission->description,
            ];
        })->groupBy('module');

        // Debug: uncomment to see the data
        // dd($permissions->toArray());

        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return Inertia::render('Role/Edit', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    /**
     * Update the role.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:roles,name,' . $role->id],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ], $this->validationMessages(), $this->validationAttributes());

        // Role admin: nama "admin" tidak boleh diubah dan permissions dipaksa lengkap.
        $isAdmin = $role->name === 'admin';

        $role->update([
            'name' => $isAdmin ? 'admin' : $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $isAdmin ? true : ($validated['is_active'] ?? true),
        ]);

        if ($isAdmin) {
            // Admin selalu punya semua permission — abaikan input dari form.
            $role->permissions()->sync(Permission::pluck('id')->toArray());
        } else {
            $role->permissions()->sync($validated['permissions'] ?? []);
        }

        return redirect()->route('role.index')
            ->with('success', 'Role berhasil diupdate');
    }

    /**
     * Remove the role.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting admin role
        if ($role->name === 'admin') {
            return back()->with('error', 'Tidak bisa menghapus role admin');
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Tidak bisa menghapus role yang masih memiliki user');
        }

        $role->delete();

        return redirect()->route('role.index')
            ->with('success', 'Role berhasil dihapus');
    }

    /**
     * Custom Indonesian validation messages.
     */
    protected function validationMessages(): array
    {
        return [
            'name.required'         => 'Nama role wajib diisi.',
            'name.string'           => 'Nama role harus berupa teks.',
            'name.max'              => 'Nama role maksimal :max karakter.',
            'name.unique'           => 'Nama role ini sudah digunakan, pilih nama lain.',
            'name.alpha_dash'       => 'Nama role hanya boleh berisi huruf, angka, strip (-), dan underscore (_), tanpa spasi.',

            'display_name.required' => 'Display name wajib diisi.',
            'display_name.string'   => 'Display name harus berupa teks.',
            'display_name.max'      => 'Display name maksimal :max karakter.',

            'description.string'    => 'Deskripsi harus berupa teks.',
            'description.max'       => 'Deskripsi maksimal :max karakter.',

            'is_active.boolean'     => 'Status harus berupa Aktif atau Nonaktif.',

            'permissions.array'     => 'Format permissions tidak valid.',
            'permissions.*.exists'  => 'Salah satu permission yang dipilih tidak valid.',
        ];
    }

    /**
     * Custom field attribute names.
     */
    protected function validationAttributes(): array
    {
        return [
            'name'         => 'Nama role',
            'display_name' => 'Display name',
            'description'  => 'Deskripsi',
            'is_active'    => 'Status',
            'permissions'  => 'Permissions',
        ];
    }
}
