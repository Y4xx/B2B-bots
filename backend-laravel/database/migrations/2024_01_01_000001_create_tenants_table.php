<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('company_name')->nullable();
            $table->string('plan')->default('free'); // free, basic, pro, enterprise
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_subscription_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
