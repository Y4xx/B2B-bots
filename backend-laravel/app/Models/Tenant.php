<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'company_name',
        'plan',
        'trial_ends_at',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_subscription_status',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    protected $hidden = [
        'stripe_customer_id',
        'stripe_subscription_id',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function getRateLimit(): int
    {
        return match($this->plan) {
            'free' => config('app.rate_limit_free', 10),
            'basic' => config('app.rate_limit_basic', 100),
            'pro' => config('app.rate_limit_pro', 1000),
            'enterprise' => config('app.rate_limit_enterprise', 10000),
            default => 10,
        };
    }

    public function canAddSite(): bool
    {
        $limits = [
            'free' => 1,
            'basic' => 5,
            'pro' => 20,
            'enterprise' => 100,
        ];

        return $this->sites()->count() < ($limits[$this->plan] ?? 1);
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->stripe_subscription_status === 'active' || $this->isOnTrial();
    }
}
