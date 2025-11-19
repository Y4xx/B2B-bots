<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Jobs\StartCrawlJob;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $sites = Site::forTenant($tenant->id)
            ->with('crawlJobs')
            ->latest()
            ->paginate(20);

        return response()->json($sites);
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        if (!$tenant->canAddSite()) {
            return response()->json([
                'error' => 'Site limit reached for your plan. Please upgrade.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|url|max:255',
            'crawl_config' => 'nullable|array',
            'crawl_config.max_pages' => 'nullable|integer|min:1|max:10000',
            'crawl_config.allowed_paths' => 'nullable|array',
            'crawl_config.excluded_paths' => 'nullable|array',
            'widget_config' => 'nullable|array',
        ]);

        // Normalize domain
        $domain = parse_url($validated['domain'], PHP_URL_HOST) ?? $validated['domain'];

        // Check if domain already exists for this tenant
        if (Site::forTenant($tenant->id)->where('domain', $domain)->exists()) {
            return response()->json([
                'error' => 'This domain is already registered.',
            ], 422);
        }

        $site = Site::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'domain' => $domain,
            'status' => 'pending',
            'crawl_config' => $validated['crawl_config'] ?? [
                'max_pages' => 100,
                'allowed_paths' => [],
                'excluded_paths' => [],
            ],
            'widget_config' => $validated['widget_config'] ?? [
                'theme' => 'light',
                'position' => 'bottom-right',
                'greeting' => 'Hi! How can I help you today?',
            ],
        ]);

        // Dispatch crawl job
        StartCrawlJob::dispatch($site);

        return response()->json($site, 201);
    }

    public function show(Request $request, $id)
    {
        $tenant = app('tenant');

        $site = Site::forTenant($tenant->id)
            ->with(['documents', 'crawlJobs'])
            ->findOrFail($id);

        return response()->json($site);
    }

    public function update(Request $request, $id)
    {
        $tenant = app('tenant');

        $site = Site::forTenant($tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'crawl_config' => 'sometimes|array',
            'widget_config' => 'sometimes|array',
        ]);

        $site->update($validated);

        return response()->json($site);
    }

    public function destroy(Request $request, $id)
    {
        $tenant = app('tenant');

        $site = Site::forTenant($tenant->id)->findOrFail($id);
        $site->delete();

        return response()->json([
            'message' => 'Site deleted successfully',
        ]);
    }

    public function reindex(Request $request, $id)
    {
        $tenant = app('tenant');

        $site = Site::forTenant($tenant->id)->findOrFail($id);

        if (!$site->canReindex()) {
            return response()->json([
                'error' => 'Site cannot be reindexed at this time.',
            ], 422);
        }

        $site->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        // Dispatch crawl job
        StartCrawlJob::dispatch($site);

        return response()->json([
            'message' => 'Reindexing started',
            'site' => $site,
        ]);
    }
}
