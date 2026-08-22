<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'title', 'slug', 'content',
        'role_target', 'sort_order', 'views_count', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'views_count' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->whereNull('role_target')
                ->orWhere('role_target', 'all')
                ->orWhere('role_target', $role);
        });
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
