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
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->date('date')
                  ->comment('The date the availability applies to.');

            $table->unique(['user_id','date']);
               

            $table->boolean('is_available')
                  ->default(true)
                  ->comment('TRUE = Available (Wants to work), FALSE = Unavailable (Vacation/Off).');

            $table->date('submission_date')
                  ->nullable()
                  ->comment('When the employee actually submitted the availability request to the system.');
            
            $table->text('notes')
                  ->nullable()
                  ->comment('Optional shift details or managerial comments.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
