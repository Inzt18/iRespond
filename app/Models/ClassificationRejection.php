<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClassificationRejection extends Model
{
    protected $fillable = [
        'user_id',
        'mobile_number',
        'predicted_label',
        'reason',
        'confidence',
        'margin',
        'probabilities',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'margin' => 'float',
            'probabilities' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
