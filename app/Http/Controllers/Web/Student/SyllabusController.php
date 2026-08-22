<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\TeacherSyllabus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SyllabusController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $enrollment = Enrollment::with(['section.subjectAssignments' => function ($q) {
            $q->where('status', true);
        }])
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->first();

        $assignmentIds = $enrollment?->section?->subjectAssignments?->pluck('id') ?? collect();

        $syllabi = TeacherSyllabus::with(['subjectAssignment.subject', 'subjectAssignment.section', 'term'])
            ->published()
            ->whereIn('subject_assignment_id', $assignmentIds)
            ->orderBy('published_at', 'desc')
            ->paginate(15);

        return Inertia::render('Student/Syllabi/Index', [
            'syllabi' => $syllabi,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $syllabus = TeacherSyllabus::with(['subjectAssignment.subject', 'subjectAssignment.section', 'term', 'creator'])
            ->published()
            ->findOrFail($id);

        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('status', 'active')
            ->whereHas('section.subjectAssignments', fn ($q) => $q->where('id', $syllabus->subject_assignment_id))
            ->first();

        if (! $enrollment) {
            abort(403, 'No tienes acceso a este cronograma.');
        }

        return Inertia::render('Student/Syllabi/Show', [
            'syllabus' => $syllabus,
        ]);
    }

    public function download(Request $request, int $id)
    {
        $user = $request->user();
        $syllabus = TeacherSyllabus::published()->findOrFail($id);

        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('status', 'active')
            ->whereHas('section.subjectAssignments', fn ($q) => $q->where('id', $syllabus->subject_assignment_id))
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        if (! $syllabus->file_path || ! \Storage::disk('public')->exists($syllabus->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return \Storage::disk('public')->download($syllabus->file_path, $syllabus->file_name);
    }
}
