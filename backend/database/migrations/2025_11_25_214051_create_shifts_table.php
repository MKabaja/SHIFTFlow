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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('schedule_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date');

            $table->foreignId('position_id')
                ->constrained('positions')
                ->cascadeOnDelete();

            $table->time('shift_start');

            $table->time('shift_end');

            $table->unsignedSmallInteger('minutes_worked')->nullable();

            $table->enum('status', ['scheduled', 'cancelled'])->default('scheduled');

            $table->decimal('hourly_rate', 8, 2)->nullable();

            $table->text('notes')->nullable();

            $table->index('user_id');
            $table->index('date');
            $table->index(['user_id', 'date']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
