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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('month');
            $table->string('department');
            $table->string('position');
            $table->decimal('base_salary', 10, 2);
            $table->decimal('position_allowance', 10, 2);
            $table->decimal('overtime_pay', 10, 2)->nullable();
            $table->decimal('leave_days', 5, 2)->nullable();
            $table->decimal('deducted_leave_pay', 10, 2)->nullable();
            $table->decimal('payable_salary', 10, 2);
            $table->decimal('social_security', 10, 2);
            $table->decimal('income_tax', 10, 2)->nullable();
            $table->decimal('net_salary', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
