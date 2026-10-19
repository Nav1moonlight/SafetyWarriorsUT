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
        Schema::table('safety_reports', function (Blueprint $table) {
            $table->string('activity_indicator')->nullable()->after('department'); // Nama indikator kegiatan asli
            $table->string('indicator_category')->nullable()->default('EHS')->after('activity_indicator'); // EHS / CSR / EHS / CSR
            $table->boolean('has_direct_action')->default(false)->after('has_action'); // Penanganan Langsung (+3)
            $table->boolean('has_ehs_activity')->default(false)->after('has_direct_action'); // Mengikuti Kegiatan EHS (+2)
            $table->boolean('has_csr_activity')->default(false)->after('has_ehs_activity'); // Mengikuti Kegiatan CSR (+2)
            $table->boolean('has_speaker_activity')->default(false)->after('has_csr_activity'); // Menjadi Narasumber / Pemateri (+4)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('safety_reports', function (Blueprint $table) {
            $table->dropColumn([
                'activity_indicator',
                'indicator_category',
                'has_direct_action',
                'has_ehs_activity',
                'has_csr_activity',
                'has_speaker_activity',
            ]);
        });
    }
};
