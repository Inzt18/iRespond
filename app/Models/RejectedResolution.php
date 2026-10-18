<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Permanent admin-side record of a responder resolution photo that was
 * rejected. Kept separately because the responder side can delete or
 * replace the rejected photo on the emergency report itself.
 */
class RejectedResolution extends Model
{
    protected $fillable = [
        'emergency_report_id',
        'responder_id',
        'rejected_by',
        'report_type',
        'source_image_path',
        'archived_image_path',
        'note',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return ['rejected_at' => 'datetime'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(EmergencyReport::class, 'emergency_report_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function imageUrl(): ?string
    {
        $disk = Storage::disk('public');

        foreach ([$this->archived_image_path, $this->source_image_path] as $path) {
            if ($path && $disk->exists($path)) {
                return $disk->url($path);
            }
        }

        return null;
    }
}
