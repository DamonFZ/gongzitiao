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
        Schema::table('salaries', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)->comment('0-未读, 1-已读(未签), 2-已签名');
            $table->string('signature_path')->nullable()->after('status')->comment('签名图片相对路径');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn(['status', 'signature_path']);
        });
    }
};
