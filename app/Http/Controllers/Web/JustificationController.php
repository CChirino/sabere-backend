<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Justification;
use Inertia\Inertia;

class JustificationController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Justification::class);

        return Inertia::render('Justifications/Index');
    }

    public function create()
    {
        $this->authorize('create', Justification::class);

        return Inertia::render('Justifications/Create');
    }

    public function show(Justification $justification)
    {
        $this->authorize('view', $justification);

        return Inertia::render('Justifications/Show', ['justification' => $justification->load(['student', 'guardian', 'academicPeriod', 'reviewedBy'])]);
    }

    public function review(Justification $justification)
    {
        $this->authorize('review', $justification);

        return Inertia::render('Justifications/Review', ['justification' => $justification->load(['student', 'guardian', 'academicPeriod'])]);
    }
}
