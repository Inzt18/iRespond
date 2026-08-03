<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyReport extends Model
{
    protected $fillable = [
        'user_id',
        'image_path',
        'predicted_type',
        'confirmed_type',
        'confidence',
        'latitude',
        'longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
