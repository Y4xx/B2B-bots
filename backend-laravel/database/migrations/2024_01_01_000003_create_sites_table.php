<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('domain');
            $table->string('api_key')->unique();
            $table->string('status')->default('pending'); // pending, crawling, indexing, active, failed
            $table->integer('pages_crawled')->default(0);
            $table->integer('documents_indexed')->default(0);
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamp('last_indexed_at')->nullable();
            $table->json('crawl_config')->nullable(); // max_pages, allowed_paths, excluded_paths
            $table->json('widget_config')->nullable(); // theme, position, greeting
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'domain']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
