<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Events\NewChatMessage;
use App\Models\Section;
use App\Models\SectionChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SectionChatController extends Controller
{
    /**
     * Mostrar la lista de secciones donde el profesor tiene asignaciones.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Obtener las secciones únicas donde el profesor tiene asignaciones activas
        $sections = Section::whereHas('subjectAssignments', function ($query) use ($user) {
            $query->where('teacher_id', $user->id)
                  ->where('status', true);
        })
        ->with(['grade', 'academicPeriod'])
        ->get()
        ->map(fn($section) => [
            'id' => $section->id,
            'name' => $section->name,
            'full_name' => $section->fullName,
            'grade' => $section->grade->name,
            'academic_period' => $section->academicPeriod->name,
            'students_count' => $section->enrollments()->where('status', 'active')->count(),
        ]);

        return Inertia::render('Teacher/Chat/Index', [
            'sections' => $sections,
        ]);
    }

    /**
     * Mostrar el chat de una sección específica.
     */
    public function show(Section $section)
    {
        $user = Auth::user();
        
        // Verificar que el profesor tiene asignación en esta sección
        $hasAssignment = $section->subjectAssignments()
            ->where('teacher_id', $user->id)
            ->where('status', true)
            ->exists();

        if (!$hasAssignment) {
            return redirect()->route('teacher.chat.index')
                ->with('error', 'No tienes acceso a esta sección.');
        }

        // Obtener los mensajes del chat
        $messages = SectionChatMessage::where('section_id', $section->id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'asc')
            ->get();

        // Obtener los estudiantes de la sección
        $students = $section->students()
            ->select('users.id', 'users.name', 'users.email')
            ->get();

        // Obtener los profesores de la sección
        $teachers = $section->subjectAssignments()
            ->where('status', true)
            ->with('teacher:id,name,email')
            ->get()
            ->pluck('teacher')
            ->unique('id')
            ->values();

        return Inertia::render('Teacher/Chat/Show', [
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'full_name' => $section->fullName,
                'grade' => $section->grade->name,
                'academic_period' => $section->academicPeriod->name,
            ],
            'messages' => $messages->map(fn($msg) => [
                'id' => $msg->id,
                'message' => $msg->message,
                'type' => $msg->type,
                'attachment_url' => $msg->attachment_url,
                'attachment_name' => $msg->attachment_name,
                'user' => [
                    'id' => $msg->user->id,
                    'name' => $msg->user->name,
                ],
                'is_mine' => $msg->user_id === $user->id,
                'created_at' => $msg->created_at->format('Y-m-d H:i:s'),
                'time' => $msg->created_at->format('H:i'),
                'date' => $msg->created_at->format('d/m/Y'),
            ]),
            'students' => $students,
            'teachers' => $teachers,
            'currentUserId' => $user->id,
        ]);
    }

    /**
     * Enviar un mensaje al chat.
     */
    public function store(Request $request, Section $section)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = Auth::user();

        // Verificar que el profesor tiene asignación en esta sección
        $hasAssignment = $section->subjectAssignments()
            ->where('teacher_id', $user->id)
            ->where('status', true)
            ->exists();

        if (!$hasAssignment) {
            return back()->with('error', 'No tienes acceso a esta sección.');
        }

        // Crear el mensaje
        $message = SectionChatMessage::create([
            'section_id' => $section->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'type' => 'text',
        ]);

        // Cargar la relación del usuario
        $message->load('user:id,name');

        // Disparar evento para broadcasting en tiempo real
        broadcast(new NewChatMessage($message))->toOthers();

        return back()->with('success', 'Mensaje enviado.');
    }

    /**
     * Eliminar un mensaje (solo el autor puede eliminarlo).
     */
    public function destroy(SectionChatMessage $message)
    {
        $user = Auth::user();

        // Verificar que el mensaje pertenece al usuario
        if ($message->user_id !== $user->id) {
            return back()->with('error', 'No puedes eliminar este mensaje.');
        }

        $message->delete();

        return back()->with('success', 'Mensaje eliminado.');
    }
}
