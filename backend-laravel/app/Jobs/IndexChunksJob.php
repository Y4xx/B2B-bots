<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    protected $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle(): void
    {
        $chunks = $this->document->chunks()->notIndexed()->get();

        if ($chunks->isEmpty()) {
            $this->document->update([
                'status' => 'indexed',
                'indexed_at' => now(),
            ]);
            return;
        }

        try {
            // Prepare chunks for indexing
            $chunksData = $chunks->map(function ($chunk) {
                return [
                    'id' => $chunk->id,
                    'content' => $chunk->content,
                    'metadata' => [
                        'tenant_id' => $chunk->tenant_id,
                        'document_id' => $chunk->document_id,
                        'document_url' => $this->document->url,
                        'document_title' => $this->document->title,
                        'chunk_index' => $chunk->chunk_index,
                    ],
                ];
            });

            // Call Python indexer service
            $response = Http::timeout(60)->post(
                config('services.python.indexer_url') . '/index',
                [
                    'tenant_id' => $this->document->tenant_id,
                    'site_id' => $this->document->site_id,
                    'chunks' => $chunksData,
                ]
            );

            if ($response->successful()) {
                $result = $response->json();

                // Update chunks with vector IDs
                foreach ($result['indexed_chunks'] ?? [] as $indexedChunk) {
                    $chunk = $chunks->firstWhere('id', $indexedChunk['chunk_id']);
                    if ($chunk) {
                        $chunk->update([
                            'vector_id' => $indexedChunk['vector_id'],
                            'is_indexed' => true,
                            'indexed_at' => now(),
                        ]);
                    }
                }

                $this->document->update([
                    'status' => 'indexed',
                    'indexed_at' => now(),
                ]);

                // Update site status if all documents are indexed
                $this->updateSiteStatus();

                Log::info("Successfully indexed document {$this->document->id}");
            } else {
                throw new \Exception("Indexer service returned error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Failed to index document {$this->document->id}: " . $e->getMessage());
            
            $this->document->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }

    protected function updateSiteStatus(): void
    {
        $site = $this->document->site;
        
        $totalDocuments = $site->documents()->count();
        $indexedDocuments = $site->documents()->where('status', 'indexed')->count();

        $site->update([
            'documents_indexed' => $indexedDocuments,
            'last_indexed_at' => now(),
        ]);

        // If all documents are indexed, mark site as active
        if ($totalDocuments > 0 && $totalDocuments === $indexedDocuments) {
            $site->update(['status' => 'active']);
        }
    }
}
