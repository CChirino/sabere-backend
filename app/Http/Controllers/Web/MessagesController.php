<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class MessagesController extends Controller
{
    public function index()
    {
        return Inertia::render('Messages/Index');
    }

    public function show(int $id)
    {
        return Inertia::render('Messages/Show', ['messageId' => $id]);
    }

    public function create()
    {
        return Inertia::render('Messages/Compose');
    }
}
