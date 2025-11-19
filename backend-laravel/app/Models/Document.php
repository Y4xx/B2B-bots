<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'url',
        'title',
        'content',
        'cleaned_content',
        'content_hash',
        'word_count',
        'metadata',
        'status',
        'crawled_at',
        'indexed_at',
    ];

    protected $casts = [
        'word_count' => 'integer',
        'metadata' => 'array',
        'crawled_at' => 'datetime',
        'indexed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeIndexed($query)
    {
        return $query->where('status', 'indexed');
    }

    public function getExcerpt(int $length = 200): string
    {
        $content = $this->cleaned_content ?? $this->content;
        return Str::limit($content, $length);
    }
}
