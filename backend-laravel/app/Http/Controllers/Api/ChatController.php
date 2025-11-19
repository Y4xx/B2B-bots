<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\RagChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected $ragService;

    public function __construct(RagChatService $ragService)
    {
        $this->ragService = $ragService;
    }

    /**
     * Handle chat requests from the widget
     * This endpoint is publicly accessible via API key
     */
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string',
            'visitor_metadata' => 'nullable|array',
        ]);

        // Find site by API key
        $site = Site::where('api_key', $validated['api_key'])->first();

        if (!$site) {
            return response()->json([
                'error' => 'Invalid API key',
            ], 401);
        }

        if ($site->status !== 'active') {
            return response()->json([
                'error' => 'Chatbot is not ready yet. Please try again later.',
            ], 503);
        }

        // Check tenant subscription
        if (!$site->tenant->hasActiveSubscription()) {
            return response()->json([
                'error' => 'Service unavailable. Please contact the site owner.',
            ], 503);
        }

        // Get or create conversation
        $sessionId = $validated['session_id'] ?? Str::uuid();
        $conversation = ChatConversation::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'tenant_id' => $site->tenant_id,
                'site_id' => $site->id,
                'visitor_ip' => $request->ip(),
                'visitor_user_agent' => $request->userAgent(),
                'visitor_metadata' => $validated['visitor_metadata'] ?? [],
            ]
        );

        // Save user message
        ChatMessage::create([
            'tenant_id' => $site->tenant_id,
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'message' => $validated['message'],
        ]);

        // Get AI response from RAG service
        $response = $this->ragService->getResponse(
            $site,
            $validated['message'],
            $conversation
        );

        // Save assistant message
        $assistantMessage = ChatMessage::create([
            'tenant_id' => $site->tenant_id,
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'message' => $response['message'],
            'sources' => $response['sources'] ?? [],
            'metadata' => $response['metadata'] ?? [],
        ]);

        return response()->json([
            'session_id' => $sessionId,
            'message' => $response['message'],
            'sources' => $response['sources'] ?? [],
            'metadata' => $response['metadata'] ?? [],
        ]);
    }

    /**
     * Get chat conversations for a site (admin)
     */
    public function conversations(Request $request, $siteId)
    {
        $tenant = app('tenant');

        $site = Site::forTenant($tenant->id)->findOrFail($siteId);

        $conversations = ChatConversation::where('site_id', $site->id)
            ->with('messages')
            ->latest()
            ->paginate(20);

        return response()->json($conversations);
    }

    /**
     * Get messages for a conversation (admin)
     */
    public function messages(Request $request, $conversationId)
    {
        $tenant = app('tenant');

        $conversation = ChatConversation::forTenant($tenant->id)
            ->with('messages')
            ->findOrFail($conversationId);

        return response()->json($conversation);
    }
}
