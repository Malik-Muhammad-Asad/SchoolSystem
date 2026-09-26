<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('final_results', function (Blueprint $table) {
            $table->string('report_type')->nullable()->after('term_id');
        });

        // Initialize report_type based on existing exam_id values
        // If exam_id is null, it was 'multi' (Half Yearly)
        // If exam_id is NOT null, it was 'single' (Grand Test)
        DB::table('final_results')->whereNotNull('exam_id')->update(['report_type' => 'single']);
        DB::table('final_results')->whereNull('exam_id')->update(['report_type' => 'multi']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('final_results', function (Blueprint $table) {
            $table->dropColumn('report_type');
        });
    }
};
