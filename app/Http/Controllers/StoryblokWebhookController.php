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
 *
 * Where the plan has no webhook secret, a shared key in the URL (`?key=...`) is the fallback.
 * It is weaker: the key can end up in logs of anything that sees the URL. Either proof is enough.
 */
class StoryblokWebhookController extends Controller
{
    public function __invoke(Request $request, StoryblokContent $storyblok): JsonResponse
    {
        $secret = (string) config('storyblok.webhook_secret');
        $key = (string) config('storyblok.webhook_key');

        if ($secret === '' && $key === '') {
            return response()->json(['error' => 'Webhook secret is not configured.'], 503);
        }

        if (! $this->signatureIsValid($request, $secret) && ! $this->keyIsValid($request, $key)) {
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

    private function signatureIsValid(Request $request, string $secret): bool
    {
        if ($secret === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha1', $request->getContent(), $secret), (string) $request->header('webhook-signature'));
    }

    private function keyIsValid(Request $request, string $key): bool
    {
        $given = $request->query('key');

        return $key !== '' && is_string($given) && hash_equals($key, $given);
    }
}
