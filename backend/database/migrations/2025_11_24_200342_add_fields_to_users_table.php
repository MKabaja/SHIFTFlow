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
            $table->string('pin_hashed', 60)
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->enum('role', ['employee', 'manager', 'admin'])
                ->default('employee');

            $table->decimal('hourly_rate', 8, 2)
                ->nullable();

            $table->unsignedSmallInteger('max_hours_per_month')
                ->nullable();

            $table->unsignedSmallInteger('min_break_hours')
                ->default(11);

            $table->enum('contract_type', ['employment_contract', 'mandate_contract'])
                ->default('employment_contract');

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
                'is_active',
                'contract_type',
                'hourly_rate',
                'max_hours_per_month',
                'min_break_hours',

            ]);
        });
    }
};
