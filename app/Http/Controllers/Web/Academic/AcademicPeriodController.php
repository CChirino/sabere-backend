<?php

namespace App\Http\Controllers\Web\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;

class AcademicPeriodController extends Controller
{
    /**
     * Store a newly created academic period.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:academic_periods'],
            'school_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['boolean'],
        ]);

        AcademicPeriod::create($validated);

        return back()->with('success', 'Período académico creado correctamente.');
    }

    /**
     * Update the specified academic period.
     */
    public function update(Request $request, AcademicPeriod $period)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:academic_periods,code,' . $period->id],
            'school_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['boolean'],
        ]);

        $period->update($validated);

        return back()->with('success', 'Período académico actualizado correctamente.');
    }

    /**
     * Remove the specified academic period.
     */
    public function destroy(AcademicPeriod $period)
    {
        // Check if period has related data
        if ($period->sections()->count() > 0 || $period->enrollments()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el período porque tiene secciones o inscripciones asociadas.');
        }

        $period->delete();

        return back()->with('success', 'Período académico eliminado correctamente.');
    }
}
