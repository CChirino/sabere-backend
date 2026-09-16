<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'default_severity',
        'requires_notification',
        'description',
        'is_active',
    ];

    protected $casts = [
        'requires_notification' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function disciplinaryRecords(): HasMany
    {
        return $this->hasMany(DisciplinaryRecord::class);
    }
}
