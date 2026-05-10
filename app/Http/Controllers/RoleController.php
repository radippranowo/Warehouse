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
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

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
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

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
}
