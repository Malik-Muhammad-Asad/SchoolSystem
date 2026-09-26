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
        Schema::table('final_results', function (Blueprint $table) {
            // class_id column add karo
            $table->foreignId('class_id')->after('student_id')->constrained('class')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('final_results', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn('class_id');
        });
    }

};
