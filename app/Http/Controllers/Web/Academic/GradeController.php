<?php

namespace App\Http\Controllers\Web\Academic;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GradeController extends Controller
{
    /**
     * Store a newly created grade.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'education_level_id' => ['required', 'exists:education_levels,id'],
            'name' => ['required', 'string', 'max:255'],
            'numeric_equivalent' => ['required', 'integer', 'min:1', 'max:12'],
            'status' => ['boolean'],
        ]);

        // Check unique constraint
        $exists = Grade::where('education_level_id', $validated['education_level_id'])
            ->where('numeric_equivalent', $validated['numeric_equivalent'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['numeric_equivalent' => 'Ya existe un grado con este equivalente numérico en el nivel seleccionado.']);
        }

        Grade::create($validated);

        return back()->with('success', 'Grado creado correctamente.');
    }

    /**
     * Update the specified grade.
     */
    public function update(Request $request, Grade $grade)
    {
        $validated = $request->validate([
            'education_level_id' => ['required', 'exists:education_levels,id'],
            'name' => ['required', 'string', 'max:255'],
            'numeric_equivalent' => ['required', 'integer', 'min:1', 'max:12'],
            'status' => ['boolean'],
        ]);

        // Check unique constraint excluding current
        $exists = Grade::where('education_level_id', $validated['education_level_id'])
            ->where('numeric_equivalent', $validated['numeric_equivalent'])
            ->where('id', '!=', $grade->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['numeric_equivalent' => 'Ya existe un grado con este equivalente numérico en el nivel seleccionado.']);
        }

        $grade->update($validated);

        return back()->with('success', 'Grado actualizado correctamente.');
    }

    /**
     * Remove the specified grade.
     */
    public function destroy(Grade $grade)
    {
        if ($grade->sections()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el grado porque tiene secciones asociadas.');
        }

        $grade->delete();

        return back()->with('success', 'Grado eliminado correctamente.');
    }
}
