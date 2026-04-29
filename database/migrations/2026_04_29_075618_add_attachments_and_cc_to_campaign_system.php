<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة CC على مستوى الحملة
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('cc_emails')->nullable()->after('target_role');
        });

        // إضافة المرفقات على مستوى الرسالة (القالب)
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('body');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('cc_emails');
        });
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};