<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\HelpSuggestion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->user()->roles->first()?->name ?? 'student';

        $categories = HelpCategory::active()
            ->with(['articles' => fn ($q) => $q->active()->forRole($role)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Help/Index', [
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $role = $request->user()->roles->first()?->name ?? 'student';

        $article = HelpArticle::active()
            ->forRole($role)
            ->where('slug', $slug)
            ->with('category')
            ->firstOrFail();

        $article->incrementViews();

        return Inertia::render('Help/Show', [
            'article' => $article,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        $role = $request->user()->roles->first()?->name ?? 'student';

        $results = collect();
        if ($query && strlen($query) >= 3) {
            $results = HelpArticle::active()
                ->forRole($role)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%");
                })
                ->with('category')
                ->limit(20)
                ->get();
        }

        return Inertia::render('Help/Search', [
            'results' => $results,
            'query' => $query,
        ]);
    }

    public function storeSuggestion(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:article,question',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        HelpSuggestion::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
        ]);

        return redirect()->route('help.index')->with('success', 'Tu sugerencia ha sido enviada correctamente.');
    }
}
