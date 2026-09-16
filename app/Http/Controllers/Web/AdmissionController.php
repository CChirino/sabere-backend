<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AdmissionController extends Controller
{
    public function index()
    {
        return Inertia::render('Admissions/Index');
    }

    public function create()
    {
        return Inertia::render('Admissions/Create');
    }

    public function show(int $id)
    {
        return Inertia::render('Admissions/Show', ['admissionId' => $id]);
    }
}
