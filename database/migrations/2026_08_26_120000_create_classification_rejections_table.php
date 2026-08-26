<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classification_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mobile_number')->nullable()->index();
            $table->string('predicted_label');
            $table->string('reason')->index(); // non_emergency, low_confidence, uncertain
            $table->decimal('confidence', 8, 6)->nullable();
            $table->decimal('margin', 8, 6)->nullable();
            $table->json('probabilities')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classification_rejections');
    }
};
