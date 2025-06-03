<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('academic_years', function (Blueprint $table) {
        $table->id();
        $table->string('year'); // e.g. 2024-2025
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->boolean('is_current')->default(false); // This marks the current academic year
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
