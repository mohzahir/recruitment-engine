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
        // إضافة عمود لتتبع حالة الشركة
        Schema::table('leads', function (Blueprint $table) {
            $table->string('enrichment_stage')->default('pending'); 
            // الحالات ستكون: pending, completed_apollo, completed_lusha, completed_hunter, failed_all
        });

        // إضافة عمود لمعرفة مصدر الإيميل
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('source')->nullable(); // apollo, lusha, hunter
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
