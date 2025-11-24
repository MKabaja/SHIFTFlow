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
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin_hashed',60)
                  ->nullable()
                  ->comment('Hashed PIN for quick login (not unique)'); 

            $table ->boolean('is_active')
                   ->default(true)
                   ->comment('Mark an employee as active/inactive');

            $table->enum('role', ['employee','manager','admin'])
                  ->default('employee')
                  ->comment('Employees roles');

            $table->json('positions')
                  ->nullable() 
                  ->comment('Job titles');

            $table->decimal('hourly_rate',8,2)
                  ->nullable()
                  ->comment('Hourly pay');

            $table->unsignedSmallInteger('max_hours_per_month')
                  ->nullable()
                  ->comment('Max working hours per month - set by admin per user/contract');
                  

            $table->unsignedSmallInteger('min_break_hours') 
                  ->default(11)
                  ->comment('Minimum rest period between shifts');

            $table->enum('contract_type', ['uop','zlecenie'])
                  ->default('uop')
                  ->comment('Employment contract type');
            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pin_hashed',
                'role',
                'positions',
                'is_active',
                'contract_type',
                'hourly_rate',
                'max_hours_per_month',
                'min_break_hours',
            ]);
        });
    }
};
