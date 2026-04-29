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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم توضيحي لك: مثلاً "رسالة التعارف الأولى"
            $table->string('subject'); // عنوان الإيميل الحقيقي الذي سيراه العميل
            $table->text('body'); // محتوى الإيميل
            $table->integer('step_number'); // ترتيب الرسالة: 1، 2، 3
            $table->integer('delay_days'); // كم يوم ينتظر النظام بعد الرسالة السابقة؟ (الرسالة الأولى ستكون 0)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
