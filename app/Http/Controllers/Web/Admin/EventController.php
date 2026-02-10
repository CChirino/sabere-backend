<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;

class EventController extends Controller
{
    /**
     * Store a newly created event.
     */
    public function store(StoreEventRequest $request)
    {
        Event::create($request->validatedWithCreator());

        return back()->with('success', 'Evento creado correctamente.');
    }

    /**
     * Update the specified event.
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $event->update($request->validated());

        return back()->with('success', 'Evento actualizado correctamente.');
    }

    /**
     * Remove the specified event.
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return back()->with('success', 'Evento eliminado correctamente.');
    }
}
