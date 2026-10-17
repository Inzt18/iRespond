<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Http\Requests\ReportExportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportExportController extends Controller
{
    private const HEADERS = [
        'Report ID',
        'Date',
        'Confirmed Type',
        'Predicted Type',
        'Confidence (%)',
        'Status',
        'Reporter Mobile',
        'Latitude',
        'Longitude',
        'Assigned Responder',
        'Flagged',
        'Flag Reason',
        'Integrity Issues',
        'Authenticity',
        'Admin Note',
        'Reviewed At',
        'Image URL',
    ];

    /**
     * Download the (optionally filtered) emergency reports as a CSV file.
     * Accepts the same filters as the admin reports page.
     */
    public function __invoke(ReportExportRequest $request): StreamedResponse
    {
        $filters = $request->filters();

        $query = EmergencyReport::query()
            ->with(['user:id,mobile_number', 'assignedResponder:id,name'])
            ->filter($filters)
            ->orderByDesc('id');

        $tz = config('app.timezone');
        $filename = 'emergency-reports-'.now($tz)->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query, $tz) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens the file with the right encoding.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::HEADERS, ',', '"', '');

            $query->chunk(500, function ($reports) use ($out, $tz) {
                foreach ($reports as $report) {
                    fputcsv($out, [
                        $report->id,
                        $report->created_at?->timezone($tz)->format('Y-m-d H:i:s'),
                        $report->confirmed_type,
                        $report->predicted_type,
                        $report->confidence !== null ? number_format($report->confidence * 100, 1, '.', '') : '',
                        $report->status,
                        $this->phone($report->user?->mobile_number),
                        $report->latitude,
                        $report->longitude,
                        $this->cell($report->assignedResponder?->name),
                        $report->flagged ? 'yes' : 'no',
                        $this->cell($report->flag_reason),
                        implode('; ', $report->integrityIssues()),
                        $report->authenticity_status,
                        $this->cell($report->admin_note),
                        $report->reviewed_at?->timezone($tz)->format('Y-m-d H:i:s'),
                        $report->imageUrl(),
                    ], ',', '"', '');
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Neutralise CSV/formula injection: user-supplied text that starts with
     * = + - @ would be executed as a formula when opened in Excel.
     */
    private function cell(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /**
     * Keep the leading zero of mobile numbers (09xx...) when opened in Excel.
     */
    private function phone(?string $number): string
    {
        $digits = preg_replace('/[^0-9+]/', '', (string) $number);

        return $digits === '' ? '' : '="'.$digits.'"';
    }
}
