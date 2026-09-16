<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class StudentProfileController extends Controller
{
    public function show(int $studentId)
    {
        return Inertia::render('StudentProfiles/Show', ['studentId' => $studentId]);
    }

    public function edit(int $studentId)
    {
        return Inertia::render('StudentProfiles/Edit', ['studentId' => $studentId]);
    }
}
