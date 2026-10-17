<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filters shared by the CSV and PDF report exports
 * (same values the admin reports page uses).
 */
class ReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the 'admin' route middleware already guards access
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:pending,responding,resolved,invalid'],
            'type' => ['nullable', 'in:fire,medical,natural_disaster'],
            'search' => ['nullable', 'string', 'max:50'],
            'flagged' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array{status: ?string, type: ?string, search: ?string, flagged: bool}
     */
    public function filters(): array
    {
        $data = $this->validated();

        return [
            'status' => $data['status'] ?? null,
            'type' => $data['type'] ?? null,
            'search' => $data['search'] ?? null,
            'flagged' => $this->boolean('flagged'),
        ];
    }
}
