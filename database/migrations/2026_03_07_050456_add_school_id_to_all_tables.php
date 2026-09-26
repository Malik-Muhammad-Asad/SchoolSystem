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
        $tables = [
            'class', 'students', 'terms', 'exams', 'subjects', 'exam_results',
            'class_subjects', 'student_test_marks', 'academic_years', 'student_transfers',
            'final_results', 'student_imports'
        ];
        
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Since this might run on an existing database, nullable is safe
                $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'classes', 'students', 'terms', 'exams', 'subjects', 'exam_results',
            'class_subjects', 'student_test_marks', 'academic_years', 'student_transfers',
            'final_results', 'student_imports'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            });
        }
    }
};
