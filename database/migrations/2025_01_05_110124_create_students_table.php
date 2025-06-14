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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Student's name
            $table->string('father_name');    // Father's name
            $table->string('gr_no')->unique()->nullable(); // GR (General Register) number, unique
            $table->foreignId('class_id')->constrained('class')->onDelete('cascade'); // Foreign key for class
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('students');
    }
};
