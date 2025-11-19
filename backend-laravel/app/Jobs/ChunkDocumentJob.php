<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ChunkDocumentJob implements ShouldQueue
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
        $content = $this->document->cleaned_content ?? $this->document->content;
        
        // Simple chunking strategy: split by paragraphs/sentences
        // Target ~500-1000 tokens per chunk (roughly 375-750 words)
        $chunks = $this->chunkText($content, 750);

        foreach ($chunks as $index => $chunkContent) {
            DocumentChunk::updateOrCreate(
                [
                    'document_id' => $this->document->id,
                    'chunk_index' => $index,
                ],
                [
                    'tenant_id' => $this->document->tenant_id,
                    'content' => $chunkContent,
                    'token_count' => $this->estimateTokens($chunkContent),
                    'is_indexed' => false,
                ]
            );
        }

        $this->document->update(['status' => 'chunked']);

        // Dispatch indexing job
        IndexChunksJob::dispatch($this->document);
    }

    protected function chunkText(string $text, int $maxWords = 750): array
    {
        $chunks = [];
        $words = explode(' ', $text);
        $currentChunk = [];

        foreach ($words as $word) {
            $currentChunk[] = $word;

            if (count($currentChunk) >= $maxWords) {
                $chunks[] = implode(' ', $currentChunk);
                $currentChunk = [];
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = implode(' ', $currentChunk);
        }

        return array_filter($chunks);
    }

    protected function estimateTokens(string $text): int
    {
        // Rough estimate: 1 token ≈ 0.75 words
        return (int) (str_word_count($text) / 0.75);
    }
}
