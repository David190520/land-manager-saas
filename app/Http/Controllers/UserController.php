<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::min(8)],
            'role'     => 'required|in:admin,accountant,secretary',
        ], [
            'email.unique' => 'Ya existe un usuario con este correo electrónico.',
        ]);

        User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => $validated['password'],
            'role'      => $validated['role'],
            'tenant_id' => $tenantId,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $tenantId = $request->user()->tenant_id;

        if ($user->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'role'      => 'required|in:admin,accountant,secretary',
            'is_active' => 'required|boolean',
            'password'  => ['nullable', Password::min(8)],
        ]);

        // Prevent the only admin from being demoted or deactivated
        if ($user->id === $request->user()->id && (!$validated['is_active'] || $validated['role'] !== 'admin')) {
            return redirect()->back()->withErrors(['user' => 'No puede modificar su propio rol o desactivar su cuenta.']);
        }

        $data = [
            'name'      => $validated['name'],
            'role'      => $validated['role'],
            'is_active' => $validated['is_active'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $user)
    {
        $tenantId = $request->user()->tenant_id;

        if ($user->tenant_id !== $tenantId) {
            abort(403);
        }

        if ($user->id === $request->user()->id) {
            return redirect()->back()->withErrors(['user' => 'No puede eliminar su propia cuenta.']);
        }

        $user->delete();

        return redirect()->back()->with('success', 'Usuario eliminado.');
    }
}
