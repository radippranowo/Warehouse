<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $roleFilter = $request->input('role', '');
        $statusFilter = $request->input('status', '');
        $perPage = min(max((int) $request->input('per_page', 20), 10), 100);

        $query = User::with('role')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter, function ($q) use ($roleFilter) {
                $q->where('role_id', $roleFilter);
            })
            ->when($statusFilter !== '', function ($q) use ($statusFilter) {
                $q->where('is_active', (bool) $statusFilter);
            })
            ->orderBy('created_at', 'desc');

        $users = $query->paginate($perPage)->withQueryString();

        $roles = Role::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'display_name']);

        return Inertia::render('User/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'display_name']);

        return Inertia::render('User/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['boolean'],
        ], $this->validationMessages(), $this->validationAttributes());

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('user.index')
            ->with('success', 'User berhasil ditambahkan');
    }

    /**
     * Show the form for editing the user.
     */
    public function edit(User $user)
    {
        $roles = Role::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'display_name']);

        return Inertia::render('User/Edit', [
            'user' => $user->load('role'),
            'roles' => $roles,
        ]);
    }

    /**
     * Update the user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['boolean'],
        ], $this->validationMessages(), $this->validationAttributes());

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('user.index')
            ->with('success', 'User berhasil diupdate');
    }

    /**
     * Remove the user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting own account
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri');
        }

        // Prevent deleting last admin
        if ($user->hasRole('admin')) {
            $adminCount = User::whereHas('role', function ($q) {
                $q->where('name', 'admin');
            })->count();

            if ($adminCount <= 1) {
                return back()->with('error', 'Tidak bisa menghapus admin terakhir');
            }
        }

        $user->delete();

        return redirect()->route('user.index')
            ->with('success', 'User berhasil dihapus');
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user)
    {
        // Prevent deactivating own account
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa menonaktifkan akun sendiri');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "User berhasil {$status}");
    }

    /**
     * Custom Indonesian validation messages.
     */
    protected function validationMessages(): array
    {
        return [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'name.string'        => 'Nama lengkap harus berupa teks.',
            'name.max'           => 'Nama lengkap maksimal :max karakter.',

            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid (contoh: nama@example.com).',
            'email.max'          => 'Email maksimal :max karakter.',
            'email.unique'       => 'Email ini sudah terdaftar, gunakan email lain.',

            'password.required'  => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min'       => 'Password minimal :min karakter.',
            'password.letters'   => 'Password harus mengandung minimal satu huruf.',
            'password.mixed'     => 'Password harus mengandung huruf besar dan kecil.',
            'password.numbers'   => 'Password harus mengandung minimal satu angka.',
            'password.symbols'   => 'Password harus mengandung minimal satu simbol.',
            'password.uncompromised' => 'Password ini terdeteksi pernah bocor, gunakan password lain.',

            'role_id.required'   => 'Role wajib dipilih.',
            'role_id.exists'     => 'Role yang dipilih tidak valid.',

            'is_active.boolean'  => 'Status harus berupa Aktif atau Nonaktif.',
        ];
    }

    /**
     * Custom field attribute names (digunakan saat field disebut di pesan default).
     */
    protected function validationAttributes(): array
    {
        return [
            'name'      => 'Nama lengkap',
            'email'     => 'Email',
            'password'  => 'Password',
            'role_id'   => 'Role',
            'is_active' => 'Status',
        ];
    }
}
