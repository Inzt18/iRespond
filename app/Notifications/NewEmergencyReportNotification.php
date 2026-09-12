<?php

namespace App\Notifications;

use App\Models\EmergencyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEmergencyReportNotification extends Notification
{
    use Queueable;

    public function __construct(public EmergencyReport $report) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $type = str_replace('_', ' ', (string) $this->report->confirmed_type);

        return [
            'type' => 'new_emergency_report',
            'title' => 'New emergency report',
            'message' => 'Report #'.$this->report->id.' · '.ucwords($type),
            'report_id' => $this->report->id,
            'confirmed_type' => $this->report->confirmed_type,
            'status' => $this->report->status,
            'url' => route('admin.reports.show', $this->report),
        ];
    }
}
