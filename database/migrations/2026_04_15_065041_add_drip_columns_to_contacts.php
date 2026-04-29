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
        Schema::table('contacts', function (Blueprint $table) {
            // لمعرفة في أي خطوة من تسلسل الإيميلات يتواجد هذا الشخص حالياً
            $table->integer('current_step')->default(0); 
            // لتسجيل وقت إرسال آخر إيميل لكي نحسب مدة الانتظار (الـ 3 أيام مثلاً)
            $table->timestamp('last_emailed_at')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            //
        });
    }
};
