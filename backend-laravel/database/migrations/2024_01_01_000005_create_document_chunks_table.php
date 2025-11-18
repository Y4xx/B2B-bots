<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->integer('chunk_index');
            $table->text('content');
            $table->integer('token_count')->default(0);
            $table->string('vector_id')->nullable(); // ID in vector database
            $table->boolean('is_indexed')->default(false);
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['document_id', 'chunk_index']);
            $table->index(['tenant_id', 'is_indexed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
