<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('safety_reports', function (Blueprint $table) {
            $table->id();
            $table->string('sheet_row_id')->nullable()->index();
            $table->dateTime('reported_at')->index();
            $table->string('reporter_name')->index();
            $table->string('department')->index();
            $table->boolean('has_unsafe_act')->default(false); // +5 poin
            $table->boolean('has_unsafe_condition')->default(false); // +3 poin
            $table->boolean('has_suggestion')->default(false); // +3 poin
            $table->boolean('has_action')->default(false); // +4 poin
            $table->integer('points')->default(0);
            $table->text('description')->nullable();
            $table->text('photo_url')->nullable();
            $table->string('location')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('safety_reports');
    }
};
