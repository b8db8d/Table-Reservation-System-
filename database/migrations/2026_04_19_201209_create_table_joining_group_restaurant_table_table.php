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
        Schema::create('table_joining_group_restaurant_table', function (Blueprint $table) {
            $table->foreignId('table_joining_group_id')
                ->references('id')->on('table_joining_groups')
                ->name('fk_tjgroup_rt_group_id')
                ->cascadeOnDelete();
            $table->foreignId('restaurant_table_id')
                ->references('id')->on('restaurant_tables')
                ->name('fk_tjgroup_rt_table_id')
                ->cascadeOnDelete();
            $table->primary(['table_joining_group_id', 'restaurant_table_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_joining_group_restaurant_table');
    }
};
