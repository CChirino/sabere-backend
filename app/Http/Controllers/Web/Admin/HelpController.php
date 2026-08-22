<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\HelpSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HelpController extends Controller
{
    public function index()
    {
        $categories = HelpCategory::withCount('articles')->orderBy('sort_order')->paginate(20);
        $suggestions = HelpSuggestion::with('user')->orderBy('created_at', 'desc')->limit(5)->get();
        $pendingSuggestions = HelpSuggestion::pending()->count();

        return Inertia::render('Admin/Help/Index', [
            'categories' => $categories,
            'suggestions' => $suggestions,
            'pendingSuggestions' => $pendingSuggestions,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'integer',
        ]);

        HelpCategory::create([
            ...$validated,
            'slug' => Str::slug($validated['name']),
        ]);

        return Redirect::route('admin.help.index')->with('success', 'Categoría creada.');
    }

    public function updateCategory(Request $request, int $id)
    {
        $category = HelpCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return Redirect::route('admin.help.index')->with('success', 'Categoría actualizada.');
    }

    public function destroyCategory(int $id)
    {
        HelpCategory::findOrFail($id)->delete();

        return Redirect::route('admin.help.index')->with('success', 'Categoría eliminada.');
    }

    public function storeArticle(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:help_categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'role_target' => 'nullable|in:all,student,teacher,guardian,admin',
            'sort_order' => 'integer',
        ]);

        HelpArticle::create([
            ...$validated,
            'slug' => Str::slug($validated['title']).'-'.time(),
        ]);

        return Redirect::route('admin.help.index')->with('success', 'Artículo creado.');
    }

    public function updateArticle(Request $request, int $id)
    {
        $article = HelpArticle::findOrFail($id);
        $validated = $request->validate([
            'category_id' => 'required|exists:help_categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'role_target' => 'nullable|in:all,student,teacher,guardian,admin',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $article->update($validated);

        return Redirect::route('admin.help.index')->with('success', 'Artículo actualizado.');
    }

    public function destroyArticle(int $id)
    {
        HelpArticle::findOrFail($id)->delete();

        return Redirect::route('admin.help.index')->with('success', 'Artículo eliminado.');
    }

    public function suggestions()
    {
        $suggestions = HelpSuggestion::with('user', 'reviewer')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Help/Suggestions', [
            'suggestions' => $suggestions,
        ]);
    }

    public function respondSuggestion(Request $request, int $id)
    {
        $suggestion = HelpSuggestion::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewed,implemented,rejected',
            'admin_response' => 'nullable|string|max:5000',
        ]);

        $suggestion->update([
            ...$validated,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return Redirect::route('admin.help.suggestions')->with('success', 'Respuesta enviada.');
    }
}
