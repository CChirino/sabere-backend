<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DisciplinaryRecord;
use Inertia\Inertia;

class DisciplineController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', DisciplinaryRecord::class);

        return Inertia::render('Discipline/Index');
    }

    public function create()
    {
        $this->authorize('create', DisciplinaryRecord::class);

        return Inertia::render('Discipline/Create');
    }

    public function show(DisciplinaryRecord $record)
    {
        $this->authorize('view', $record);

        return Inertia::render('Discipline/Show', ['record' => $record->load(['student', 'incidentType', 'recordedBy'])]);
    }
}
