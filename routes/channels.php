<?php

use App\Models\Section;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Section Chat Channel
|--------------------------------------------------------------------------
|
| Canal privado para el chat de cada sección. Solo pueden acceder:
| - Estudiantes inscritos en la sección
| - Profesores con asignaciones en la sección
|
*/
Broadcast::channel('section-chat.{sectionId}', function ($user, $sectionId) {
    $section = Section::find($sectionId);
    
    if (!$section) {
        return false;
    }

    // Verificar si es estudiante inscrito en la sección
    $isStudent = $user->enrollments()
        ->where('section_id', $sectionId)
        ->where('status', 'active')
        ->exists();

    if ($isStudent) {
        return ['id' => $user->id, 'name' => $user->name, 'role' => 'student'];
    }

    // Verificar si es profesor con asignación en la sección
    $isTeacher = $section->subjectAssignments()
        ->where('teacher_id', $user->id)
        ->where('status', true)
        ->exists();

    if ($isTeacher) {
        return ['id' => $user->id, 'name' => $user->name, 'role' => 'teacher'];
    }

    return false;
});
