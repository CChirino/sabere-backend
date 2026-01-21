<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Imports\UsersImport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:create users')->only(['store', 'import']);
        $this->middleware('permission:edit users')->only(['update']);
        $this->middleware('permission:delete users')->only(['destroy']);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->syncRoles($validated['roles']);

        return back()->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->syncRoles($validated['roles']);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * Import users from Excel/CSV file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'], // Max 5MB
        ], [
            'file.required' => 'Debe seleccionar un archivo.',
            'file.mimes' => 'El archivo debe ser de tipo Excel (.xlsx, .xls) o CSV.',
            'file.max' => 'El archivo no debe superar los 5MB.',
        ]);

        $import = new UsersImport();
        Excel::import($import, $request->file('file'));

        $imported = $import->getImportedCount();
        $skipped = $import->getSkippedCount();
        $errors = $import->getErrors();

        if ($imported > 0 && empty($errors)) {
            return back()->with('success', "Se importaron {$imported} usuarios correctamente.");
        }

        if ($imported > 0 && !empty($errors)) {
            return back()
                ->with('warning', "Se importaron {$imported} usuarios. {$skipped} filas fueron omitidas.")
                ->with('import_errors', $errors);
        }

        return back()
            ->with('error', 'No se pudo importar ningún usuario.')
            ->with('import_errors', $errors);
    }

    /**
     * Download import template.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="plantilla_usuarios.csv"',
        ];

        $columns = ['nombre', 'email', 'contrasena', 'roles'];
        $examples = [
            ['Juan Pérez', 'juan@ejemplo.com', 'password123', 'student'],
            ['María García', 'maria@ejemplo.com', 'password123', 'teacher'],
            ['Carlos López', 'carlos@ejemplo.com', 'password123', 'student,guardian'],
        ];

        $callback = function() use ($columns, $examples) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, $columns);
            foreach ($examples as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
