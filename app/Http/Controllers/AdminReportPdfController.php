<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportExportRequest;
use App\Models\EmergencyReport;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminReportPdfController extends Controller
{
    /** Keeps PDF generation within shared-hosting memory/time limits. */
    private const MAX_ROWS = 500;

    public function __invoke(ReportExportRequest $request)
    {
        $filters = $request->filters();
        $tz = config('app.timezone');

        $base = EmergencyReport::query()->filter($filters);

        $total = (clone $base)->count();

        $byType = (clone $base)->reorder()
            ->selectRaw('confirmed_type, count(*) as c')
            ->groupBy('confirmed_type')->pluck('c', 'confirmed_type');

        $byStatus = (clone $base)->reorder()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')->pluck('c', 'status');

        $flagged = (clone $base)->where('flagged', true)->count();

        $reports = (clone $base)
            ->with(['user:id,mobile_number', 'assignedResponder:id,name'])
            ->orderByDesc('id')
            ->limit(self::MAX_ROWS)
            ->get();

        $pdf = Pdf::loadView('reports.pdf', [
            'reports' => $reports,
            'total' => $total,
            'truncated' => $total > self::MAX_ROWS,
            'maxRows' => self::MAX_ROWS,
            'byType' => $byType,
            'byStatus' => $byStatus,
            'flagged' => $flagged,
            'filters' => $filters,
            'generatedAt' => now($tz),
            'generatedBy' => $request->user()?->name ?? $request->user()?->username,
            'tz' => $tz,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('emergency-reports-'.now($tz)->format('Y-m-d_His').'.pdf');
    }
}
