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
        Schema::create('position_user', function (Blueprint $table) {

            // user Key
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // position Key
            $table->foreignId('position_id')
                ->constrained()
                ->onDelete('cascade');

            $table->timestamps();
            // safely against dublicates
            $table->unique(['user_id', 'position_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_user');
    }
};
