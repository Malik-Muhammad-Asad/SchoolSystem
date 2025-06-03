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
    Schema::table('class', function (Blueprint $table) {
        $table->foreignId('academic_year_id')->nullable(); // Don't constrain yet
    });

    // Set value for existing rows
    // DB::statement('UPDATE class SET academic_year_id = (SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1)');

    // Now add the constraint after valid data exists
    Schema::table('class', function (Blueprint $table) {
        $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
    });
}

};
