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
        Schema::create('joining_group_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_joining_group_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->nullable()->comment('0=Sunday, 1=Monday … 6=Saturday. Null means the restriction applies every day.');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('joining_group_restrictions');
    }
};
