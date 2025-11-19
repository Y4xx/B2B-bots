<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\BillingPortal\Session as BillingPortalSession;

class BillingController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'plan' => 'required|in:basic,pro,enterprise',
        ]);

        $prices = [
            'basic' => config('services.stripe.price_basic'),
            'pro' => config('services.stripe.price_pro'),
            'enterprise' => config('services.stripe.price_enterprise'),
        ];

        $session = Session::create([
            'customer' => $tenant->stripe_customer_id,
            'customer_email' => !$tenant->stripe_customer_id ? $tenant->email : null,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $prices[$validated['plan']],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => config('app.frontend_url') . '/billing?success=true',
            'cancel_url' => config('app.frontend_url') . '/billing?canceled=true',
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan' => $validated['plan'],
            ],
        ]);

        return response()->json([
            'checkout_url' => $session->url,
        ]);
    }

    public function createPortalSession(Request $request)
    {
        $tenant = app('tenant');

        if (!$tenant->stripe_customer_id) {
            return response()->json([
                'error' => 'No subscription found',
            ], 404);
        }

        $session = BillingPortalSession::create([
            'customer' => $tenant->stripe_customer_id,
            'return_url' => config('app.frontend_url') . '/billing',
        ]);

        return response()->json([
            'portal_url' => $session->url,
        ]);
    }

    public function getCurrentSubscription(Request $request)
    {
        $tenant = app('tenant');

        return response()->json([
            'plan' => $tenant->plan,
            'status' => $tenant->stripe_subscription_status,
            'trial_ends_at' => $tenant->trial_ends_at,
            'is_on_trial' => $tenant->isOnTrial(),
            'has_active_subscription' => $tenant->hasActiveSubscription(),
            'rate_limit' => $tenant->getRateLimit(),
        ]);
    }
}
