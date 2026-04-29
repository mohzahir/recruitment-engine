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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Software Engineer Outreach"
            $table->integer('step_number'); // 1 (Intro), 2 (Follow-up), etc.
            $table->string('subject'); // e.g., "Regarding your {{job_title}} role at {{company}}"
            $table->text('body');
            $table->integer('delay_days')->default(0); // Days to wait after the previous step
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
