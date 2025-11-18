<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Document;
use App\Models\CrawlJob;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * Handle callbacks from Python scraper service
     */
    public function scraperCallback(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|integer',
            'tenant_id' => 'required|integer',
            'status' => 'required|string|in:success,error',
            'pages' => 'nullable|array',
            'pages.*.url' => 'required|string',
            'pages.*.title' => 'nullable|string',
            'pages.*.content' => 'required|string',
            'pages.*.cleaned_content' => 'nullable|string',
            'pages.*.metadata' => 'nullable|array',
            'error' => 'nullable|string',
        ]);

        $site = Site::where('id', $validated['site_id'])
            ->where('tenant_id', $validated['tenant_id'])
            ->firstOrFail();

        if ($validated['status'] === 'error') {
            $site->update([
                'status' => 'failed',
                'error_message' => $validated['error'] ?? 'Crawl failed',
            ]);

            return response()->json(['message' => 'Error recorded']);
        }

        // Process crawled pages
        foreach ($validated['pages'] as $page) {
            $contentHash = md5($page['content']);
            
            Document::updateOrCreate(
                [
                    'site_id' => $site->id,
                    'url' => $page['url'],
                ],
                [
                    'tenant_id' => $site->tenant_id,
                    'title' => $page['title'] ?? null,
                    'content' => $page['content'],
                    'cleaned_content' => $page['cleaned_content'] ?? null,
                    'content_hash' => $contentHash,
                    'word_count' => str_word_count($page['content']),
                    'metadata' => $page['metadata'] ?? [],
                    'status' => 'pending',
                    'crawled_at' => now(),
                ]
            );
        }

        $site->update([
            'status' => 'crawling',
            'pages_crawled' => $site->documents()->count(),
            'last_crawled_at' => now(),
        ]);

        return response()->json(['message' => 'Pages received']);
    }

    /**
     * Handle Stripe webhooks
     */
    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle different event types
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;

            case 'customer.subscription.updated':
            case 'customer.subscription.created':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
        }

        return response()->json(['received' => true]);
    }

    protected function handleCheckoutCompleted($session)
    {
        $tenantId = $session->metadata->tenant_id ?? null;
        if (!$tenantId) return;

        $tenant = \App\Models\Tenant::find($tenantId);
        if (!$tenant) return;

        $tenant->update([
            'stripe_customer_id' => $session->customer,
            'stripe_subscription_id' => $session->subscription,
        ]);
    }

    protected function handleSubscriptionUpdated($subscription)
    {
        $tenant = \App\Models\Tenant::where('stripe_subscription_id', $subscription->id)->first();
        if (!$tenant) return;

        $plan = $subscription->metadata->plan ?? 'basic';

        $tenant->update([
            'plan' => $plan,
            'stripe_subscription_status' => $subscription->status,
        ]);
    }

    protected function handleSubscriptionDeleted($subscription)
    {
        $tenant = \App\Models\Tenant::where('stripe_subscription_id', $subscription->id)->first();
        if (!$tenant) return;

        $tenant->update([
            'plan' => 'free',
            'stripe_subscription_status' => 'canceled',
        ]);
    }

    protected function handlePaymentSucceeded($invoice)
    {
        // Log successful payment
    }

    protected function handlePaymentFailed($invoice)
    {
        // Notify tenant about failed payment
    }
}
