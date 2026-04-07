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
        // 1. JUST Create Shelves Table
        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique(); // e.g. "A-01", "B-05"
            $table->boolean('is_occupied')->default(false); // True = Full, False = Empty
            $table->timestamps();
        });
        
        // REMOVED THE PARCELS BLOCK FROM HERE
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shelves');
    }
};