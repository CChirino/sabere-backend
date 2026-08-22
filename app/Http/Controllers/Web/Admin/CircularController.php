<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendCircularNotifications;
use App\Models\AcademicPeriod;
use App\Models\Circular;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CircularController extends Controller
{
    public function index(Request $request)
    {
        $circulars = Circular::with('creator', 'academicPeriod')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $periods = AcademicPeriod::select('id', 'name')->orderBy('start_date', 'desc')->get();

        return Inertia::render('Admin/Circulars/Index', [
            'circulars' => $circulars,
            'periods' => $periods,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'audience' => 'required|in:all,teachers,students,guardians,staff',
            'academic_period_id' => 'nullable|exists:academic_periods,id',
            'send_email' => 'boolean',
            'send_push' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $circular = Circular::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $validated['priority'],
            'audience' => $validated['audience'],
            'academic_period_id' => $validated['academic_period_id'] ?? null,
            'send_email' => $validated['send_email'] ?? false,
            'send_push' => $validated['send_push'] ?? false,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        if (empty($validated['scheduled_at'])) {
            SendCircularNotifications::dispatch($circular);
        }

        return Redirect::route('admin.circulars.index')->with('success', 'Circular creada correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $circular = Circular::findOrFail($id);

        if ($circular->isSent()) {
            return Redirect::back()->with('error', 'No se puede editar una circular ya enviada.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'audience' => 'required|in:all,teachers,students,guardians,staff',
            'academic_period_id' => 'nullable|exists:academic_periods,id',
            'send_email' => 'boolean',
            'send_push' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $circular->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $validated['priority'],
            'audience' => $validated['audience'],
            'academic_period_id' => $validated['academic_period_id'] ?? null,
            'send_email' => $validated['send_email'] ?? false,
            'send_push' => $validated['send_push'] ?? false,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
        ]);

        return Redirect::route('admin.circulars.index')->with('success', 'Circular actualizada correctamente.');
    }

    public function destroy(int $id)
    {
        $circular = Circular::findOrFail($id);
        $circular->delete();

        return Redirect::route('admin.circulars.index')->with('success', 'Circular eliminada correctamente.');
    }

    public function sendNow(int $id)
    {
        $circular = Circular::findOrFail($id);

        if ($circular->isSent()) {
            return Redirect::back()->with('error', 'La circular ya fue enviada.');
        }

        SendCircularNotifications::dispatch($circular);

        return Redirect::back()->with('success', 'Circular enviada correctamente.');
    }
}
