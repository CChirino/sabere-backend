<?php

namespace App\Http\Controllers\Web\Academic;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Store a newly created subject.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_area_id' => ['required', 'exists:subject_areas,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:subjects'],
            'description' => ['nullable', 'string'],
            'status' => ['boolean'],
        ]);

        Subject::create($validated);

        return back()->with('success', 'Materia creada correctamente.');
    }

    /**
     * Update the specified subject.
     */
    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'subject_area_id' => ['required', 'exists:subject_areas,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code,' . $subject->id],
            'description' => ['nullable', 'string'],
            'status' => ['boolean'],
        ]);

        $subject->update($validated);

        return back()->with('success', 'Materia actualizada correctamente.');
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(Subject $subject)
    {
        if ($subject->assignments()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la materia porque tiene asignaciones asociadas.');
        }

        $subject->delete();

        return back()->with('success', 'Materia eliminada correctamente.');
    }
}
