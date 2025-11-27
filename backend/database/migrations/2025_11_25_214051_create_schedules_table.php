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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('creates a user_id column as a Foreign Key (FK) linking the current tables record to the users table');
            $table->date('date');

            $table->enum('position',['B1','B2','B3','B4','B5','B6','B7','B8','PW','PW2','WR','WR2','WR3','WS','WS2','SR','K1','K2','TGT','TG','PTG','PTG2','OTG','OTG2','BT'])
            ->comment('Salt mine position/location (B1-B8, PW, WR, WS, etc.)');;

            $table->time('shift_start');

            $table->time('shift_end');

            $table->unsignedSmallInteger('hours_worked')    ->nullable();

            $table->enum('status', ['scheduled','completed','cancelled','vacation','unavailable'])->default('scheduled');

            $table->decimal('hourly_rate',8,2)->nullable();

            $table->text('notes')->nullable();

            $table->index('user_id');
            $table->index('date');
            $table->index(['user_id','date']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
