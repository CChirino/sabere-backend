<?php

namespace App\Http\Controllers\Web\Academic;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * Store a newly created section.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade_id' => ['required', 'exists:grades,id'],
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'name' => ['required', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['boolean'],
        ]);

        Section::create($validated);

        return back()->with('success', 'Sección creada correctamente.');
    }

    /**
     * Update the specified section.
     */
    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'grade_id' => ['required', 'exists:grades,id'],
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'name' => ['required', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['boolean'],
        ]);

        $section->update($validated);

        return back()->with('success', 'Sección actualizada correctamente.');
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Section $section)
    {
        if ($section->enrollments()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la sección porque tiene inscripciones asociadas.');
        }

        $section->delete();

        return back()->with('success', 'Sección eliminada correctamente.');
    }
}
