<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. حذف جدول الحملات القديم (لتنظيف قاعدة البيانات)
        Schema::dropIfExists('campaigns');

        // 2. إنشاء جدول الحملات الجديد والذكي
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الحملة (مثال: استقطاب مدراء الموارد البشرية - الرياض)
            $table->string('target_role')->nullable(); // الكلمات المفتاحية للمنصب (مثال: HR, Human Resources)
            $table->string('status')->default('active'); // active, paused, archived
            $table->timestamps();
        });

        // 3. ربط قوالب الإيميل بالحملة
        Schema::table('email_templates', function (Blueprint $table) {
            // إضافة حقل campaign_id ليتبع القالب لحملة معينة
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('cascade');
        });

        // 4. ربط جهات الاتصال (صناع القرار) بالحملة + إضافة ذاكرة الخمول
        Schema::table('contacts', function (Blueprint $table) {
            // هذا الشخص موجود في أي حملة حالياً؟
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('set null');
            
            // هذا الحقل السحري لدورة الـ 90 يوم (إيقاظ الموتى)
            $table->timestamp('dormant_until')->nullable(); 
        });
    }

    public function down(): void
    {
        // في حال أردت التراجع عن هذه التعديلات
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn(['campaign_id', 'dormant_until']);
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn('campaign_id');
        });

        Schema::dropIfExists('campaigns');
    }
};