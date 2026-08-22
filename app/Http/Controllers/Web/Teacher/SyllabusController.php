<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SubjectAssignment;
use App\Models\TeacherSyllabus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SyllabusController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $assignmentId = $request->query('assignment_id');

        $syllabi = TeacherSyllabus::with(['subjectAssignment.subject', 'subjectAssignment.section', 'term'])
            ->where('created_by', $user->id)
            ->when($assignmentId, fn ($q) => $q->forSubject($assignmentId))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $assignments = SubjectAssignment::with(['subject', 'section'])
            ->where('teacher_id', $user->id)
            ->where('status', true)
            ->get();

        return Inertia::render('Teacher/Syllabi/Index', [
            'syllabi' => $syllabi,
            'assignments' => $assignments,
            'filters' => ['assignment_id' => $assignmentId],
        ]);
    }

    public function create(Request $request)
    {
        $assignments = SubjectAssignment::with(['subject', 'section', 'academicPeriod.terms'])
            ->where('teacher_id', $request->user()->id)
            ->where('status', true)
            ->get();

        return Inertia::render('Teacher/Syllabi/Create', [
            'assignments' => $assignments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'term_id' => 'nullable|exists:terms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'content_type' => 'required|in:file,editor,both',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'content' => 'nullable|string',
            'objectives' => 'nullable|array',
            'objectives.*' => 'string|max:500',
            'topics' => 'nullable|array',
            'topics.*.week' => 'nullable|string|max:50',
            'topics.*.topic' => 'required|string|max:255',
            'topics.*.description' => 'nullable|string|max:500',
            'evaluation_criteria' => 'nullable|array',
            'evaluation_criteria.*' => 'string|max:500',
            'resources' => 'nullable|array',
            'resources.*' => 'string|max:500',
        ]);

        $assignment = SubjectAssignment::findOrFail($validated['subject_assignment_id']);
        if ($assignment->teacher_id !== $request->user()->id) {
            abort(403, 'No tienes permiso para crear cronogramas en esta asignación.');
        }

        $data = collect($validated)->except('file')->toArray();
        $data['created_by'] = $request->user()->id;
        $data['is_published'] = false;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $data['file_path'] = $file->store('syllabi', 'public');
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
        }

        TeacherSyllabus::create($data);

        return Redirect::route('teacher.syllabi.index')->with('success', 'Cronograma creado correctamente.');
    }

    public function show(Request $request, int $id)
    {
        $syllabus = TeacherSyllabus::with(['subjectAssignment.subject', 'subjectAssignment.section', 'term', 'creator'])
            ->findOrFail($id);

        if ($syllabus->created_by !== $request->user()->id && ! $syllabus->is_published) {
            abort(403);
        }

        return Inertia::render('Teacher/Syllabi/Show', ['syllabus' => $syllabus]);
    }

    public function edit(Request $request, int $id)
    {
        $syllabus = TeacherSyllabus::findOrFail($id);
        if ($syllabus->created_by !== $request->user()->id) {
            abort(403);
        }

        $assignments = SubjectAssignment::with(['subject', 'section', 'academicPeriod.terms'])
            ->where('teacher_id', $request->user()->id)
            ->where('status', true)
            ->get();

        return Inertia::render('Teacher/Syllabi/Edit', [
            'syllabus' => $syllabus,
            'assignments' => $assignments,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $syllabus = TeacherSyllabus::findOrFail($id);
        if ($syllabus->created_by !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'term_id' => 'nullable|exists:terms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'content_type' => 'required|in:file,editor,both',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'content' => 'nullable|string',
            'objectives' => 'nullable|array',
            'objectives.*' => 'string|max:500',
            'topics' => 'nullable|array',
            'topics.*.week' => 'nullable|string|max:50',
            'topics.*.topic' => 'required|string|max:255',
            'topics.*.description' => 'nullable|string|max:500',
            'evaluation_criteria' => 'nullable|array',
            'evaluation_criteria.*' => 'string|max:500',
            'resources' => 'nullable|array',
            'resources.*' => 'string|max:500',
        ]);

        $data = collect($validated)->except('file')->toArray();

        if ($request->hasFile('file')) {
            if ($syllabus->file_path) {
                Storage::disk('public')->delete($syllabus->file_path);
            }
            $file = $request->file('file');
            $data['file_path'] = $file->store('syllabi', 'public');
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
        }

        $syllabus->update($data);

        return Redirect::route('teacher.syllabi.index')->with('success', 'Cronograma actualizado correctamente.');
    }

    public function destroy(Request $request, int $id)
    {
        $syllabus = TeacherSyllabus::findOrFail($id);
        if ($syllabus->created_by !== $request->user()->id) {
            abort(403);
        }

        if ($syllabus->file_path) {
            Storage::disk('public')->delete($syllabus->file_path);
        }

        $syllabus->delete();

        return Redirect::route('teacher.syllabi.index')->with('success', 'Cronograma eliminado correctamente.');
    }

    public function download(Request $request, int $id)
    {
        $syllabus = TeacherSyllabus::findOrFail($id);
        if (! $syllabus->file_path || ! Storage::disk('public')->exists($syllabus->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('public')->download($syllabus->file_path, $syllabus->file_name);
    }

    public function togglePublish(Request $request, int $id)
    {
        $syllabus = TeacherSyllabus::findOrFail($id);
        if ($syllabus->created_by !== $request->user()->id) {
            abort(403);
        }

        if ($syllabus->is_published) {
            $syllabus->unpublish();
            $message = 'Cronograma despublicado.';
        } else {
            $syllabus->publish();
            $message = 'Cronograma publicado.';
        }

        return Redirect::back()->with('success', $message);
    }
}
