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
        Schema::create('salary_import_errors', function (Blueprint $table) {
            $table->id();
            $table->string('month')->comment('导入的归属月');
            $table->string('name')->comment('失败的员工名');
            $table->string('department')->nullable()->comment('部门');
            $table->json('row_data')->comment('该行的原始数据');
            $table->string('error_reason')->comment('失败原因');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_import_errors');
    }
};
