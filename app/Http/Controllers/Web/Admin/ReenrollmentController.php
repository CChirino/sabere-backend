<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reenrollment;
use App\Models\AcademicPeriod;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReenrollmentController extends Controller
{
    /**
     * Display all reenrollment requests.
     */
    public function index(Request $request)
    {
        $query = Reenrollment::with([
            'student',
            'currentEnrollment.section.grade',
            'targetAcademicPeriod',
            'targetGrade',
            'targetSection',
            'processedBy'
        ])->orderByDesc('created_at');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by academic period
        if ($request->has('academic_period_id')) {
            $query->where('target_academic_period_id', $request->academic_period_id);
        }

        $reenrollments = $query->paginate(20)->withQueryString();
        $academicPeriods = AcademicPeriod::orderByDesc('start_date')->get();

        return Inertia::render('Admin/Reenrollments/Index', [
            'reenrollments' => $reenrollments,
            'academicPeriods' => $academicPeriods,
            'filters' => $request->only(['status', 'academic_period_id']),
        ]);
    }

    /**
     * Approve a reenrollment request.
     */
    public function approve(Request $request, Reenrollment $reenrollment)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!$reenrollment->isPending()) {
            return back()->with('error', 'Esta solicitud ya ha sido procesada.');
        }

        $reenrollment->approve(auth()->id(), $validated['admin_notes'] ?? null);

        // Create the actual enrollment
        $enrollment = $reenrollment->createEnrollment();

        // Send notification to student
        NotificationService::notifyReenrollmentApproved($reenrollment);

        return back()->with('success', 'Reinscripción aprobada correctamente. Se ha creado la inscripción para el estudiante.');
    }

    /**
     * Reject a reenrollment request.
     */
    public function reject(Request $request, Reenrollment $reenrollment)
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:1000'],
        ]);

        if (!$reenrollment->isPending()) {
            return back()->with('error', 'Esta solicitud ya ha sido procesada.');
        }

        $reenrollment->reject(auth()->id(), $validated['admin_notes']);

        // Send notification to student
        NotificationService::notifyReenrollmentRejected($reenrollment);

        return back()->with('success', 'Reinscripción rechazada correctamente.');
    }

    /**
     * Display reenrollment statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_requests' => Reenrollment::count(),
            'pending_count' => Reenrollment::where('status', 'pending')->count(),
            'approved_count' => Reenrollment::where('status', 'approved')->count(),
            'rejected_count' => Reenrollment::where('status', 'rejected')->count(),
            'by_period' => Reenrollment::selectRaw('target_academic_period_id, status, count(*) as count')
                ->groupBy('target_academic_period_id', 'status')
                ->with('targetAcademicPeriod')
                ->get(),
        ];

        return Inertia::render('Admin/Reenrollments/Statistics', [
            'statistics' => $stats,
        ]);
    }
}
