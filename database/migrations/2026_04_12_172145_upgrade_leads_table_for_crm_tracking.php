<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // ==========================================
            // 1. SEGMENTATION (Where they came from & who they are)
            // ==========================================
            $table->string('source')->default('linkedin')->after('company'); 
            $table->string('industry')->nullable()->after('source'); // e.g., Healthcare, Construction
            $table->string('role_type')->nullable()->after('industry'); // e.g., Medical, Blue Collar, White Collar

            // ==========================================
            // 2. LIFECYCLE TRACKING (Where they are in the pipeline)
            // ==========================================
            $table->string('status')->default('New')->after('is_email_verified');
            $table->timestamp('last_contacted_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'source', 
                'industry', 
                'role_type', 
                'status', 
                'last_contacted_at'
            ]);
        });
    }
};