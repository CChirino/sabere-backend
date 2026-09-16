<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:edit settings')->only(['update']);
    }

    public function index(): Response
    {
        $settings = Setting::all()->groupBy('group');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $group = $request->input('group');
        $key = $request->input('key');
        $value = $request->input('value');
        $type = $request->input('type', 'string');

        $setting = Setting::byKey($group, $key)->firstOrFail();

        $validatedValue = match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'file' => $this->handleFile($request->file('value'), $setting->value, $group, $key),
            default => (string) $value,
        };

        $setting->update([
            'value' => $validatedValue,
            'type' => $type,
        ]);

        return back()->with('success', 'Configuración actualizada.');
    }

    private function handleFile(?UploadedFile $file, mixed $current, string $group, string $key): ?string
    {
        if (! $file) {
            return $current;
        }

        if ($current) {
            Storage::disk('public')->delete($current);
        }

        $filename = "{$group}_{$key}_".time().'.'.$file->getClientOriginalExtension();

        return $file->storeAs('settings', $filename, 'public');
    }
}
