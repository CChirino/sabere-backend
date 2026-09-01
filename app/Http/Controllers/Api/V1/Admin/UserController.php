<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view users')->only(['index', 'show']);
        $this->middleware('permission:create users')->only(['store']);
        $this->middleware('permission:edit users')->only(['update']);
        $this->middleware('permission:delete users')->only(['destroy']);
    }

    public function index()
    {
        $users = User::with('roles')->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->syncRoles($validated['roles']);

        return response()->json($user->load('roles'), 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:8|confirmed|nullable',
            'roles' => 'sometimes|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $data = [
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json($user->load('roles'));
    }

    public function destroy(User $user)
    {
        // Prevenir eliminación de usuarios con roles administrativos
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json([
                'message' => 'No se puede eliminar un usuario con rol de administrador',
            ], 403);
        }

        // MED-05: Evitar que cualquier usuario administrativo sea eliminado
        // por otro admin/director sin salvaguardas adicionales.
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta',
            ], 403);
        }

        $user->delete();

        return response()->json(null, 204);
    }
}
