<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tlab\StoryblokTransfers\Content\StoryblokContent;

/**
 * Clears the cached Storyblok reads when Storyblok reports a content change.
 *
 * The package has no per-key purge: clearCache() empties the whole store, which
 * is why Storyblok has a cache store of its own. Storyblok signs each webhook
 * with the secret set on it (an HMAC-SHA1 of the raw body, hex, in the
 * `webhook-signature` header). The endpoint refuses everything until a secret
 * is configured, so it cannot be used to flush the cache by anyone who finds it.
 */
class StoryblokWebhookController extends Controller
{
    public function __invoke(Request $request, StoryblokContent $storyblok): JsonResponse
    {
        $secret = (string) config('storyblok.webhook_secret');

        if ($secret === '') {
            return response()->json(['error' => 'Webhook secret is not configured.'], 503);
        }

        $expected = hash_hmac('sha1', $request->getContent(), $secret);

        if (! hash_equals($expected, (string) $request->header('webhook-signature'))) {
            return response()->json(['error' => 'Invalid signature.'], 403);
        }

        $payload = json_decode($request->getContent(), true);
        $payload = is_array($payload) ? $payload : [];

        // A signed event for another space is not ours to act on.
        if (isset($payload['space_id']) && (string) $payload['space_id'] !== (string) config('storyblok.space_id')) {
            return response()->json(['ignored' => true]);
        }

        $cleared = $storyblok->clearCache();

        Log::info('Storyblok webhook', ['action' => $payload['action'] ?? null, 'slug' => $payload['full_slug'] ?? null, 'cleared' => $cleared]);

        return response()->json(['cleared' => $cleared], $cleared ? 200 : 500);
    }
}
