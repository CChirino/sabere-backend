<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Spatie\Permission\Models\Role;

class UsersImport implements ToCollection, WithHeadingRow
{
    protected array $errors = [];
    protected int $imported = 0;
    protected int $skipped = 0;
    protected array $validRoles;

    public function __construct()
    {
        $this->validRoles = Role::pluck('name')->toArray();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 porque la fila 1 es el encabezado

            // Validar datos de la fila
            $validator = Validator::make($row->toArray(), [
                'nombre' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'contrasena' => 'required|string|min:8',
                'roles' => 'required|string',
            ], [
                'nombre.required' => "Fila {$rowNumber}: El nombre es requerido",
                'email.required' => "Fila {$rowNumber}: El email es requerido",
                'email.email' => "Fila {$rowNumber}: El email no es válido",
                'contrasena.required' => "Fila {$rowNumber}: La contraseña es requerida",
                'contrasena.min' => "Fila {$rowNumber}: La contraseña debe tener al menos 8 caracteres",
                'roles.required' => "Fila {$rowNumber}: Los roles son requeridos",
            ]);

            if ($validator->fails()) {
                $this->errors = array_merge($this->errors, $validator->errors()->all());
                $this->skipped++;
                continue;
            }

            // Verificar si el email ya existe
            if (User::where('email', $row['email'])->exists()) {
                $this->errors[] = "Fila {$rowNumber}: El email {$row['email']} ya está registrado";
                $this->skipped++;
                continue;
            }

            // Procesar roles (separados por coma)
            $roles = array_map('trim', explode(',', $row['roles']));
            $validUserRoles = array_filter($roles, fn($role) => in_array($role, $this->validRoles));

            if (empty($validUserRoles)) {
                $this->errors[] = "Fila {$rowNumber}: Ningún rol válido especificado. Roles válidos: " . implode(', ', $this->validRoles);
                $this->skipped++;
                continue;
            }

            // Crear usuario
            try {
                $user = User::create([
                    'name' => $row['nombre'],
                    'email' => $row['email'],
                    'password' => Hash::make($row['contrasena']),
                ]);

                $user->syncRoles($validUserRoles);
                $this->imported++;
            } catch (\Exception $e) {
                $this->errors[] = "Fila {$rowNumber}: Error al crear usuario - " . $e->getMessage();
                $this->skipped++;
            }
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }
}
