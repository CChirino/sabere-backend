<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Models\Reenrollment;
use App\Models\AcademicPeriod;
use App\Models\Grade;
use App\Models\Section;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReenrollmentController extends Controller
{
    /**
     * Display the reenrollment form.
     */
    public function create()
    {
        $student = auth()->user();
        $currentEnrollment = $student->activeEnrollment();

        if (!$currentEnrollment) {
            return redirect()->route('student.dashboard')
                ->with('error', 'No tienes una inscripción activa para reinscribirte.');
        }

        // Verificar si ya tiene una solicitud pendiente
        $existingRequest = Reenrollment::where('student_id', $student->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return redirect()->route('student.reenrollment.status')
                ->with('info', 'Ya tienes una solicitud de reinscripción en proceso.');
        }

        // Obtener períodos académicos disponibles para reinscripción (futuros)
        $currentPeriod = $currentEnrollment->academicPeriod;
        $availablePeriods = AcademicPeriod::where('start_date', '>', $currentPeriod->end_date)
            ->where('status', true)
            ->orderBy('start_date')
            ->get();

        // Obtener grados disponibles (siguiente grado o el mismo si es multi-sección)
        $currentGrade = $currentEnrollment->section->grade;
        $nextGrade = Grade::where('education_level_id', $currentGrade->education_level_id)
            ->where('order', '>=', $currentGrade->order)
            ->orderBy('order')
            ->with('sections')
            ->get();

        return Inertia::render('Student/Reenrollment/Create', [
            'currentEnrollment' => $currentEnrollment->load('section.grade', 'academicPeriod'),
            'availablePeriods' => $availablePeriods,
            'nextGrades' => $nextGrade,
        ]);
    }

    /**
     * Store a new reenrollment request.
     */
    public function store(Request $request)
    {
        $student = auth()->user();
        $currentEnrollment = $student->activeEnrollment();

        if (!$currentEnrollment) {
            return back()->with('error', 'No tienes una inscripción activa.');
        }

        // Verificar si ya existe una solicitud pendiente
        $existingRequest = Reenrollment::where('student_id', $student->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return back()->with('error', 'Ya tienes una solicitud de reinscripción pendiente.');
        }

        $validated = $request->validate([
            'target_academic_period_id' => ['required', 'exists:academic_periods,id'],
            'target_grade_id' => ['required', 'exists:grades,id'],
            'target_section_id' => ['nullable', 'exists:sections,id'],
            'student_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $reenrollment = Reenrollment::create([
            'student_id' => $student->id,
            'current_enrollment_id' => $currentEnrollment->id,
            'target_academic_period_id' => $validated['target_academic_period_id'],
            'target_grade_id' => $validated['target_grade_id'],
            'target_section_id' => $validated['target_section_id'],
            'student_notes' => $validated['student_notes'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('student.reenrollment.status')
            ->with('success', 'Solicitud de reinscripción enviada correctamente. Recibirás una notificación cuando sea procesada.');
    }

    /**
     * Display the reenrollment status.
     */
    public function status()
    {
        $student = auth()->user();

        $reenrollments = Reenrollment::where('student_id', $student->id)
            ->with(['targetAcademicPeriod', 'targetGrade', 'targetSection', 'processedBy'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Student/Reenrollment/Status', [
            'reenrollments' => $reenrollments,
        ]);
    }

    /**
     * Cancel a pending reenrollment request.
     */
    public function cancel(Reenrollment $reenrollment)
    {
        if ($reenrollment->student_id !== auth()->id()) {
            return back()->with('error', 'No tienes permiso para cancelar esta solicitud.');
        }

        if (!$reenrollment->isPending()) {
            return back()->with('error', 'Solo puedes cancelar solicitudes pendientes.');
        }

        $reenrollment->cancel();

        return back()->with('success', 'Solicitud de reinscripción cancelada correctamente.');
    }
}
