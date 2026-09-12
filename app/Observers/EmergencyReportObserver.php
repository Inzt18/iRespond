<?php

namespace App\Observers;

use App\Models\EmergencyReport;
use App\Models\User;
use App\Notifications\NewEmergencyReportNotification;
use Illuminate\Support\Facades\Notification;

class EmergencyReportObserver
{
    /**
     * Notify active admins when a citizen submits a new emergency report.
     */
    public function created(EmergencyReport $emergencyReport): void
    {
        $admins = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new NewEmergencyReportNotification($emergencyReport));
    }
}
