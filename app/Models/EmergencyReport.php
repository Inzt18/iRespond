<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyReport extends Model
{
    public const LOW_CONFIDENCE_THRESHOLD = 0.50;

    protected $fillable = [
        'user_id',
        'image_path',
        'predicted_type',
        'confirmed_type',
        'confidence',
        'latitude',
        'longitude',
        'status',
        'admin_note',
        'flagged',
        'flag_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'flagged' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Integrity issues computed for admin monitoring (does not alter citizen API).
     *
     * @return list<string>
     */
    public function integrityIssues(): array
    {
        $issues = [];

        if ($this->predicted_type && $this->confirmed_type
            && $this->predicted_type !== $this->confirmed_type) {
            $issues[] = 'cnn_override';
        }

        if ($this->confidence !== null && $this->confidence < self::LOW_CONFIDENCE_THRESHOLD) {
            $issues[] = 'low_confidence';
        }

        if (empty($this->image_path) || $this->latitude === null || $this->longitude === null) {
            $issues[] = 'incomplete';
        }

        return $issues;
    }

    public function hasIntegrityIssues(): bool
    {
        return $this->flagged || count($this->integrityIssues()) > 0;
    }
}
