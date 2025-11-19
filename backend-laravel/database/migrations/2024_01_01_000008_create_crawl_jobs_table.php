<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->integer('pages_found')->default(0);
            $table->integer('pages_crawled')->default(0);
            $table->integer('pages_failed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_jobs');
    }
};
