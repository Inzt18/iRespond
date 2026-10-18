<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmergencyReport extends Model
{
    public const LOW_CONFIDENCE_THRESHOLD = 0.50;

    public const AUTHENTICITY_PENDING = 'pending';

    public const AUTHENTICITY_APPROVED = 'approved';

    public const AUTHENTICITY_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'assigned_responder_id',
        'image_path',
        'resolution_image_path',
        'authenticity_status',
        'authenticity_note',
        'authenticity_reviewed_at',
        'authenticity_reviewed_by',
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
            'authenticity_reviewed_at' => 'datetime',
        ];
    }

    /**
     * Shared filter used by the admin reports list and the CSV export,
     * so both always return exactly the same set of rows.
     *
     * @param  array{status?: ?string, type?: ?string, search?: ?string, flagged?: ?bool}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $status = $filters['status'] ?? '';
        $type = $filters['type'] ?? '';
        $search = trim((string) ($filters['search'] ?? ''));

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($type !== '') {
            $query->where('confirmed_type', $type);
        }

        if (! empty($filters['flagged'])) {
            $query->where('flagged', true);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('user', fn ($u) => $u->where('mobile_number', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedResponder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_responder_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function authenticityReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authenticity_reviewed_by');
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * Record a rejected resolution photo in the admin-side archive and keep a
     * copy of the image, so it survives the responder deleting/replacing it.
     * Safe to call repeatedly for the same photo.
     */
    public function archiveRejectedResolution(?int $reviewerId = null): ?RejectedResolution
    {
        $source = $this->resolution_image_path;

        if (! $source) {
            return null;
        }

        $existing = RejectedResolution::query()
            ->where('emergency_report_id', $this->id)
            ->where('source_image_path', $source)
            ->first();

        if ($existing) {
            $existing->update([
                'rejected_by' => $reviewerId ?? $existing->rejected_by,
                'note' => $this->authenticity_note ?? $existing->note,
            ]);

            return $existing;
        }

        $disk = Storage::disk('public');
        $archived = null;

        if ($disk->exists($source)) {
            $archived = 'emergencies/resolutions/rejected/'.$this->id.'-'.basename($source);
            $disk->copy($source, $archived);
        }

        return RejectedResolution::create([
            'emergency_report_id' => $this->id,
            'responder_id' => $this->assigned_responder_id,
            'rejected_by' => $reviewerId,
            'report_type' => $this->confirmed_type,
            'source_image_path' => $source,
            'archived_image_path' => $archived,
            'note' => $this->authenticity_note,
            'rejected_at' => now(),
        ]);
    }

    public function resolutionImageUrl(): ?string
    {
        if (! $this->resolution_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->resolution_image_path);
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
