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
        Schema::create('student_test_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained();
            $table->foreignId('class_id')->nullable()->constrained('class');
            $table->foreignId('subject_id')->nullable()->constrained();
            $table->foreignId('term_id')->nullable()->constrained();
            $table->string('test_name')->nullable(); // New column for test name
            $table->decimal('obtain_number', 5, 2)->nullable();
            $table->decimal('subject_number', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_test_marks');
    }
};
