<?php

namespace Tests\Support;

use Tlab\StoryblokTransfers\Client\ContentClient;
use Tlab\StoryblokTransfers\Client\ContentResponse;
use Tlab\StoryblokTransfers\Client\ResourceNotFoundException;

/**
 * An in-memory Content Delivery API built from storyblok/stories/stories.json,
 * so route tests read the same content as the real space without the network.
 * Supports what the app uses: story by slug or uuid, listings (starts_with,
 * sort_by, page, per_page, filter_query any_in_array), resolve_relations and
 * the links tree.
 */
class FakeContentClient implements ContentClient
{
    /** @var array<string, array<string, mixed>> keyed by full_slug */
    private array $stories = [];

    /** @var array<string, array<string, mixed>> folders keyed by full_slug, for the links tree */
    private array $folders = [];

    /** @param array<string, array<string, mixed>> $contentOverrides full_slug => fields merged into content */
    public function __construct(array $contentOverrides = [])
    {
        $seed = json_decode((string) file_get_contents(base_path('storyblok/stories/stories.json')), true, flags: JSON_THROW_ON_ERROR);

        $uuids = [];
        foreach ($seed as $i => $s) {
            $uuids[$s['full_slug']] = $this->uuid('story-'.$s['full_slug']);
        }

        foreach ($seed as $i => $s) {
            if ($s['is_folder'] ?? false) {
                $this->folders[$s['full_slug']] = ['id' => $i + 1, 'uuid' => $uuids[$s['full_slug']], 'name' => $s['name'], 'slug' => $s['full_slug']];

                continue;
            }

            $content = array_merge($s['content'], $contentOverrides[$s['full_slug']] ?? []);
            $content = $this->prepare($content, $uuids, $s['full_slug']);
            $published = isset($content['published_at']) ? str_replace(' ', 'T', $content['published_at']).':00.000Z' : '2026-11-01T08:00:00.000Z';

            $this->stories[$s['full_slug']] = [
                'id' => $i + 1,
                'uuid' => $uuids[$s['full_slug']],
                'name' => $s['name'],
                'slug' => basename($s['full_slug']),
                'full_slug' => $s['full_slug'],
                'parent_id' => null,
                'lang' => 'default',
                'created_at' => $published,
                'published_at' => $published,
                'first_published_at' => $published,
                'tag_list' => [],
                'translated_slugs' => [],
                'content' => $content,
            ];
        }
    }

    public function get(string $path, array $query): ContentResponse
    {
        if ($path === 'cdn/stories') {
            return $this->listing($query);
        }

        if ($path === 'cdn/links') {
            return $this->links($query);
        }

        $key = rawurldecode(substr($path, strlen('cdn/stories/')));

        $story = ($query['find_by'] ?? null) === 'uuid'
            ? collect($this->stories)->firstWhere('uuid', $key)
            : ($this->stories[$key] ?? null);

        if ($story === null) {
            throw new ResourceNotFoundException("No story {$key}");
        }

        return new ContentResponse(['story' => $story, 'rels' => $this->rels([$story], $query)]);
    }

    /** @param array<string, string> $query */
    private function listing(array $query): ContentResponse
    {
        $stories = array_values(array_filter(
            $this->stories,
            fn ($s) => str_starts_with($s['full_slug'], $query['starts_with'] ?? '') && $this->matchesFilters($s, $query),
        ));

        if (isset($query['sort_by'])) {
            [$field, $direction] = explode(':', $query['sort_by']) + [1 => 'asc'];
            $field = str_replace('content.', '', $field);
            usort($stories, fn ($a, $b) => strcmp((string) ($a['content'][$field] ?? ''), (string) ($b['content'][$field] ?? '')));
            if ($direction === 'desc') {
                $stories = array_reverse($stories);
            }
        }

        $perPage = (int) ($query['per_page'] ?? 25);
        $page = (int) ($query['page'] ?? 1);
        $slice = array_slice($stories, ($page - 1) * $perPage, $perPage);

        return new ContentResponse(['stories' => $slice, 'rels' => $this->rels($slice, $query)], count($stories), $perPage);
    }

    /** @param array<string, string> $query */
    private function links(array $query): ContentResponse
    {
        $prefix = $query['starts_with'] ?? '';
        $links = [];

        foreach ($this->folders as $slug => $f) {
            $links[$f['uuid']] = ['id' => $f['id'], 'uuid' => $f['uuid'], 'slug' => $slug, 'name' => $f['name'], 'is_folder' => true, 'parent_id' => 0, 'published' => true, 'position' => 0, 'real_path' => '/'.$slug, 'is_startpage' => false];
        }

        foreach ($this->stories as $slug => $s) {
            $links[$s['uuid']] = ['id' => $s['id'], 'uuid' => $s['uuid'], 'slug' => $slug, 'name' => $s['name'], 'is_folder' => false, 'parent_id' => 0, 'published' => true, 'position' => 0, 'real_path' => '/'.$slug, 'is_startpage' => false];
        }

        return new ContentResponse(['links' => array_filter($links, fn ($l) => str_starts_with($l['slug'], $prefix))]);
    }

    /** @param array<string, mixed> $story @param array<string, string> $query */
    private function matchesFilters(array $story, array $query): bool
    {
        foreach ($query as $key => $value) {
            if (! preg_match('/^filter_query\[(.+)\]\[(.+)\]$/', $key, $m)) {
                continue;
            }

            [, $field, $operation] = $m;
            $actual = $story['content'][$field] ?? null;

            $ok = match ($operation) {
                'any_in_array' => count(array_intersect((array) $actual, explode(',', $value))) > 0,
                default => throw new \LogicException("FakeContentClient does not support the {$operation} filter."),
            };

            if (! $ok) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, array<string, mixed>>  $stories
     * @param  array<string, string>  $query
     * @return array<int, array<string, mixed>>
     */
    private function rels(array $stories, array $query): array
    {
        $fields = array_filter(explode(',', $query['resolve_relations'] ?? ''));
        $wanted = [];

        foreach ($stories as $story) {
            foreach ($fields as $spec) {
                [$component, $field] = explode('.', $spec);
                if (($story['content']['component'] ?? null) !== $component) {
                    continue;
                }
                foreach ((array) ($story['content'][$field] ?? []) as $uuid) {
                    $wanted[$uuid] = true;
                }
            }
        }

        return array_values(array_filter($this->stories, fn ($s) => isset($wanted[$s['uuid']])));
    }

    /** Replace uuid placeholders and give nested components and richtext bloks ids. */
    private function prepare(mixed $node, array $uuids, string $path): mixed
    {
        if (is_string($node)) {
            return preg_match('/^\{\{uuid:(.+)\}\}$/', $node, $m) ? $uuids[$m[1]] : $node;
        }

        if (! is_array($node)) {
            return $node;
        }

        foreach ($node as $k => $v) {
            $node[$k] = $this->prepare($v, $uuids, $path.'/'.$k);
        }

        if (isset($node['component']) && ! isset($node['_uid'])) {
            $node['_uid'] = $this->uuid($path);
        }

        if (($node['type'] ?? null) === 'blok' && ($node['attrs']['id'] ?? null) === null) {
            $node['attrs']['id'] = $this->uuid($path.'#blok');
        }

        return $node;
    }

    private function uuid(string $seed): string
    {
        $h = md5($seed);

        return sprintf('%s-%s-%s-%s-%s', substr($h, 0, 8), substr($h, 8, 4), substr($h, 12, 4), substr($h, 16, 4), substr($h, 20, 12));
    }
}
