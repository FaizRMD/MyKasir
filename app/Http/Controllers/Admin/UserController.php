<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function create()
    {
        $roles = Role::query()
            ->whereNotIn('name', ['super-admin'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'role' => [
                'required',
                Rule::exists('roles', 'name')->where(fn ($q) => $q->whereNotIn('name', ['super-admin'])),
            ],
        ], [
            'role.required' => 'Silakan pilih role pengguna.',
            'role.exists' => 'Role tidak valid.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return redirect()
            ->route('admin.users.create')
            ->with('success', 'Pengguna berhasil dibuat.');
    }
}
