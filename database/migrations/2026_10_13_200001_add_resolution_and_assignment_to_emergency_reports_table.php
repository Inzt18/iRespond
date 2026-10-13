<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_reports', function (Blueprint $table) {
            $table->foreignId('assigned_responder_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('resolution_image_path')->nullable()->after('image_path');
            $table->string('authenticity_status')->nullable()->after('resolution_image_path');
            $table->text('authenticity_note')->nullable()->after('authenticity_status');
            $table->timestamp('authenticity_reviewed_at')->nullable()->after('authenticity_note');
            $table->foreignId('authenticity_reviewed_by')->nullable()->after('authenticity_reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('emergency_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('authenticity_reviewed_by');
            $table->dropColumn([
                'authenticity_status',
                'authenticity_note',
                'authenticity_reviewed_at',
                'resolution_image_path',
            ]);
            $table->dropConstrainedForeignId('assigned_responder_id');
        });
    }
};
