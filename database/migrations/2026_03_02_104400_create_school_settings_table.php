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
        Schema::create('school_settings', function (Blueprint $col) {
            $col->id();
            $col->string('school_name')->nullable();
            $col->string('school_address')->nullable();
            $col->string('school_phone')->nullable();
            $col->string('school_email')->nullable();
            $col->string('header_image')->nullable();
            $col->string('signature_image')->nullable();
            $col->string('watermark_image')->nullable();
            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
