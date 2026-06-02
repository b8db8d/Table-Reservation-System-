<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_restaurant_table', function (Blueprint $table) {
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_table_id')->constrained()->restrictOnDelete();
            $table->primary(['reservation_id', 'restaurant_table_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_restaurant_table');
    }
};
