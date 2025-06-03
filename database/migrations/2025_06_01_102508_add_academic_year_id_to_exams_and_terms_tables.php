<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add academic_year_id to exams
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->constrained('academic_years')
                ->onDelete('cascade');
        });

        // Add academic_year_id to terms
        Schema::table('terms', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->constrained('academic_years')
                ->onDelete('cascade');
        });

       
        // Update existing exams records
        // DB::statement('
        //     UPDATE exams
        //     SET academic_year_id = (
        //         SELECT id FROM academic_years
        //         WHERE is_current = 1
        //         LIMIT 1
        //     )
        // ');

        // // Update existing terms records
        // DB::statement('
        //     UPDATE terms
        //     SET academic_year_id = (
        //         SELECT id FROM academic_years
        //         WHERE is_current = 1
        //         LIMIT 1
        //     )
        // ');
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
    }
};
