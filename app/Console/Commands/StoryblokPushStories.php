<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Creates or updates the stories in storyblok/stories/stories.json through the
 * Management API, in file order. Strings like "{{uuid:authors/sample-author}}"
 * are replaced with the uuid of that story once it exists.
 *
 * The Storyblok CLI's `stories push` is built for copying stories between
 * spaces (it needs a pulled manifest), so it does not fit seeding from scratch.
 */
class StoryblokPushStories extends Command
{
    protected $signature = 'storyblok:push-stories {--file=storyblok/stories/stories.json} {--no-publish : Save as drafts} {--dry-run : Show what would happen}';

    protected $description = 'Create or update the sample stories in the Storyblok space';

    /** @var array<string, string> full_slug => uuid */
    private array $uuids = [];

    /** @var array<string, int> full_slug => id */
    private array $ids = [];

    public function handle(): int
    {
        $file = base_path($this->option('file'));
        $stories = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        $http = $this->client();

        foreach ($stories as $story) {
            $slug = $story['full_slug'];
            $existing = $this->find($http, $slug);

            if ($existing) {
                $this->uuids[$slug] = $existing['uuid'];
                $this->ids[$slug] = $existing['id'];
            }

            if ($this->option('dry-run')) {
                $this->line(($existing ? 'update ' : 'create ').$slug);
                $this->uuids[$slug] ??= 'dry-run-uuid';

                continue;
            }

            $payload = $this->payload($story, $slug);
            $publish = $this->option('no-publish') ? 0 : 1;

            $response = $existing
                ? $http->put("stories/{$existing['id']}", ['story' => $payload, 'publish' => $publish])
                : $http->post('stories', ['story' => $payload, 'publish' => $publish]);

            if ($response->failed()) {
                throw new RuntimeException("{$slug}: HTTP {$response->status()} ".$response->body());
            }

            $saved = $response->json('story');
            $this->uuids[$slug] = $saved['uuid'];
            $this->ids[$slug] = $saved['id'];
            $this->info(($existing ? 'updated ' : 'created ').$slug);
        }

        return self::SUCCESS;
    }

    private function client(): PendingRequest
    {
        $base = rtrim(config('storyblok.management_base_uri'), '/');

        return Http::baseUrl($base.'/spaces/'.config('storyblok.space_id').'/')
            ->withHeaders(['Authorization' => config('storyblok.management_token')])
            ->acceptJson()
            ->retry(4, 1500, throw: false)
            ->timeout(30);
    }

    /** @return array<string, mixed>|null */
    private function find(PendingRequest $http, string $slug): ?array
    {
        usleep(350_000); // stay under the Management API rate limit

        $response = $http->get('stories', ['with_slug' => $slug]);

        return $response->json('stories.0');
    }

    /** @return array<string, mixed> */
    private function payload(array $story, string $slug): array
    {
        $parts = explode('/', $slug);
        $leaf = array_pop($parts);
        $parentSlug = implode('/', $parts);

        $payload = ['name' => $story['name'], 'slug' => $leaf];

        if ($parentSlug !== '') {
            $payload['parent_id'] = $this->ids[$parentSlug]
                ?? throw new RuntimeException("{$slug}: parent folder {$parentSlug} is not in the file before it.");
        }

        if ($story['is_folder'] ?? false) {
            $payload['is_folder'] = true;
            $payload['default_root'] = $story['default_root'] ?? null;

            return $payload;
        }

        $payload['content'] = $this->prepare($story['content'], $slug);

        return $payload;
    }

    /** Fill placeholders and add stable _uid / blok ids so reruns do not churn. */
    private function prepare(mixed $node, string $path): mixed
    {
        if (is_string($node)) {
            return preg_match('/^\{\{uuid:(.+)\}\}$/', $node, $m)
                ? ($this->uuids[$m[1]] ?? throw new RuntimeException("{$path}: no story {$m[1]} yet; order it earlier in the file."))
                : $node;
        }

        if (! is_array($node)) {
            return $node;
        }

        foreach ($node as $key => $value) {
            $node[$key] = $this->prepare($value, $path.'/'.$key);
        }

        if (isset($node['component']) && ! isset($node['_uid'])) {
            $node['_uid'] = $this->stableUuid($path);
        }

        if (($node['type'] ?? null) === 'blok' && isset($node['attrs']) && ($node['attrs']['id'] ?? null) === null) {
            $node['attrs']['id'] = $this->stableUuid($path.'#blok');
        }

        return $node;
    }

    private function stableUuid(string $seed): string
    {
        $h = md5($seed);

        return sprintf('%s-%s-%s-%s-%s', substr($h, 0, 8), substr($h, 8, 4), substr($h, 12, 4), substr($h, 16, 4), substr($h, 20, 12));
    }
}
