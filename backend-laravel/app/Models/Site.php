<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'domain',
        'api_key',
        'status',
        'pages_crawled',
        'documents_indexed',
        'last_crawled_at',
        'last_indexed_at',
        'crawl_config',
        'widget_config',
        'error_message',
    ];

    protected $casts = [
        'pages_crawled' => 'integer',
        'documents_indexed' => 'integer',
        'last_crawled_at' => 'datetime',
        'last_indexed_at' => 'datetime',
        'crawl_config' => 'array',
        'widget_config' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($site) {
            if (empty($site->api_key)) {
                $site->api_key = 'sk_' . Str::random(32);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function crawlJobs(): HasMany
    {
        return $this->hasMany(CrawlJob::class);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function getProgressPercentage(): int
    {
        if ($this->status === 'active') {
            return 100;
        }
        
        if ($this->status === 'pending') {
            return 0;
        }

        $total = $this->pages_crawled + max(1, $this->documents_indexed);
        $completed = $this->documents_indexed;
        
        return min(99, (int) (($completed / $total) * 100));
    }

    public function canReindex(): bool
    {
        return in_array($this->status, ['active', 'failed']);
    }
}
