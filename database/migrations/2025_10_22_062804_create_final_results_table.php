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
      Schema::create('final_results', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained()->onDelete('cascade');
        $table->foreignId('exam_id')->constrained()->onDelete('cascade');
        $table->foreignId('term_id')->constrained()->onDelete('cascade');
        $table->integer('total_marks')->default(0);
        $table->float('obtained_marks')->default(0);
        $table->float('percentage')->default(0);
        $table->string('grade', 5)->nullable();
        $table->integer('rank')->nullable();
        $table->boolean('is_locked')->default(true);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_results');
    }
};
