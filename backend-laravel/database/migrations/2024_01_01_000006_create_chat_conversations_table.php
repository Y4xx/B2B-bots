<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('visitor_ip')->nullable();
            $table->string('visitor_user_agent')->nullable();
            $table->json('visitor_metadata')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
