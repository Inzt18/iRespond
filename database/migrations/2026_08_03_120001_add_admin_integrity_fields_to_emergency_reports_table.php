<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_reports', function (Blueprint $table) {
            $table->text('admin_note')->nullable()->after('status');
            $table->boolean('flagged')->default(false)->after('admin_note');
            $table->string('flag_reason')->nullable()->after('flagged');
            $table->timestamp('reviewed_at')->nullable()->after('flag_reason');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('emergency_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['admin_note', 'flagged', 'flag_reason', 'reviewed_at']);
        });
    }
};
