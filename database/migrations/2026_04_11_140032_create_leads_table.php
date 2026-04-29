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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // e.g., linkedin, indeed, bayt
            $table->string('company');
            $table->string('job_title');
            $table->string('publisher_name')->nullable();
            $table->string('email')->nullable(); // Might be null initially before Apollo enrichment
            $table->string('phone')->nullable();
            $table->text('job_description')->nullable();
            $table->string('location')->nullable();
            $table->string('source_url')->unique(); // Prevent duplicate scrapes
            $table->boolean('is_email_verified')->default(false); // Flag after Apollo search
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
