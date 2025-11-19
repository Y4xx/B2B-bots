<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('title')->nullable();
            $table->longText('content');
            $table->longText('cleaned_content')->nullable();
            $table->string('content_hash'); // To detect changes
            $table->integer('word_count')->default(0);
            $table->json('metadata')->nullable(); // headers, description, keywords
            $table->string('status')->default('pending'); // pending, chunked, indexed, failed
            $table->timestamp('crawled_at')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['site_id', 'url']);
            $table->index(['tenant_id', 'site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
