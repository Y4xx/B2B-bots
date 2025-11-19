<?php

namespace App\Services;

use App\Models\Site;
use App\Models\ChatConversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RagChatService
{
    public function getResponse(Site $site, string $message, ChatConversation $conversation): array
    {
        try {
            // Get conversation history
            $history = $conversation->messages()
                ->latest()
                ->take(10)
                ->get()
                ->reverse()
                ->map(function ($msg) {
                    return [
                        'role' => $msg->role,
                        'content' => $msg->message,
                    ];
                })
                ->toArray();

            // Call Python RAG service
            $response = Http::timeout(30)->post(
                config('services.python.rag_url') . '/query',
                [
                    'tenant_id' => $site->tenant_id,
                    'site_id' => $site->id,
                    'query' => $message,
                    'history' => $history,
                    'max_results' => 5,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'message' => $data['answer'] ?? 'I apologize, but I could not find an answer to your question.',
                    'sources' => $data['sources'] ?? [],
                    'metadata' => [
                        'model' => $data['model'] ?? 'unknown',
                        'tokens' => $data['tokens'] ?? 0,
                        'confidence' => $data['confidence'] ?? null,
                    ],
                ];
            } else {
                Log::error("RAG service error: " . $response->body());
                
                return [
                    'message' => 'I apologize, but I encountered an error while processing your question. Please try again.',
                    'sources' => [],
                    'metadata' => ['error' => true],
                ];
            }
        } catch (\Exception $e) {
            Log::error("RAG service exception: " . $e->getMessage());

            return [
                'message' => 'I apologize, but I encountered an error. Please try again later.',
                'sources' => [],
                'metadata' => ['error' => true],
            ];
        }
    }
}
