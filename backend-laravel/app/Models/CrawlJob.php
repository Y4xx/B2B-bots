<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'status',
        'pages_found',
        'pages_crawled',
        'pages_failed',
        'started_at',
        'completed_at',
        'error_message',
        'config',
    ];

    protected $casts = [
        'pages_found' => 'integer',
        'pages_crawled' => 'integer',
        'pages_failed' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'config' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function getProgressPercentage(): int
    {
        if ($this->pages_found === 0) {
            return 0;
        }

        return min(100, (int) (($this->pages_crawled / $this->pages_found) * 100));
    }
}
