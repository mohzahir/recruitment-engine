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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            // الربط مع جدول الشركات (Leads)
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            
            $table->string('full_name')->nullable();
            $table->string('job_title')->nullable(); // مثلاً: HR Manager
            $table->string('email')->unique();
            $table->string('linkedin_url')->nullable();
            $table->string('phone_number')->nullable();
            
            $table->string('status')->default('new'); // new, emailed, responded
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
