<?php

namespace Tests\Feature;

use Tests\Support\FakeContentClient;
use Tests\Support\FakesStoryblok;
use Tests\TestCase;
use Tlab\StoryblokTransfers\Client\ContentClient;
use Tlab\StoryblokTransfers\Client\ContentResponse;
use Tlab\StoryblokTransfers\Client\StoryblokApiException;
use Tlab\StoryblokTransfers\Content\StoryblokContent;

class TagNamesTest extends TestCase
{
    use FakesStoryblok;

    private const POST = '/posts/typed-content-from-storyblok';

    public function test_tags_show_their_datasource_names_not_their_values(): void
    {
        $this->fakeStoryblok();

        $post = $this->get(self::POST)->assertOk();
        $post->assertSee('<span class="tag">#PHP</span> <span class="tag">#Storyblok</span>', false);
        $post->assertDontSee('#php', false);

        // The listings and the featured post use the same names.
        $this->get('/')->assertOk()->assertSee('<span class="tag">#PHP</span>', false)->assertDontSee('#php', false);
        $this->get('/categories/laravel')->assertOk()->assertSee('<span class="tag">#Laravel</span>', false);
    }

    public function test_a_multi_word_name_stays_together_and_is_escaped(): void
    {
        $this->fakeStoryblok([], ['tags' => ['php' => 'Domain driven design', 'storyblok' => '<b>bold</b> & "quoted"']]);

        $this->get(self::POST)
            ->assertOk()
            ->assertSee('<span class="tag">#Domain driven design</span>', false)
            ->assertSee('<span class="tag">#&lt;b&gt;bold&lt;/b&gt; &amp; &quot;quoted&quot;</span>', false)
            ->assertDontSee('<b>bold</b>', false);
    }

    public function test_the_names_reach_the_social_metadata_too(): void
    {
        $this->fakeStoryblok([], ['tags' => ['php' => 'PHP 8', 'storyblok' => 'Storyblok CMS']]);

        $html = $this->get(self::POST)->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="article:tag" content="PHP 8">', $html);

        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);
        $this->assertSame('PHP 8, Storyblok CMS', json_decode($m[1], true)['keywords']);
    }

    public function test_a_tag_without_an_entry_falls_back_to_its_value(): void
    {
        $this->fakeStoryblok(['posts/typed-content-from-storyblok' => ['tags' => ['php', 'brand-new-tag']]], ['tags' => ['php' => 'PHP']]);

        $this->get(self::POST)
            ->assertOk()
            ->assertSee('<span class="tag">#PHP</span> <span class="tag">#brand-new-tag</span>', false);
    }

    public function test_a_space_without_a_tags_datasource_still_works_and_shows_the_values(): void
    {
        // The datasource does not exist: the API answers 404 and the package throws ResourceNotFoundException.
        $client = $this->fakeStoryblok([], ['tags' => null]);

        $this->get(self::POST)->assertOk()->assertSee('<span class="tag">#php</span> <span class="tag">#storyblok</span>', false);

        $reads = array_filter($client->requests, fn ($r) => $r['path'] === 'cdn/datasource_entries');
        $this->assertCount(1, $reads, 'It did ask, and the 404 was handled.');
    }

    public function test_a_failed_datasource_read_does_not_break_the_page(): void
    {
        $inner = new FakeContentClient;
        $failing = new class($inner) implements ContentClient
        {
            public int $datasourceCalls = 0;

            public function __construct(private readonly FakeContentClient $inner) {}

            public function get(string $path, array $query): ContentResponse
            {
                if ($path === 'cdn/datasource_entries') {
                    $this->datasourceCalls++;

                    throw new StoryblokApiException('datasource endpoint is down');
                }

                return $this->inner->get($path, $query);
            }
        };

        $this->app->scoped(StoryblokContent::class, fn () => StoryblokContent::usingClient($failing, config('storyblok.namespace')));
        $this->app->forgetInstance(StoryblokContent::class);

        $this->get(self::POST)->assertOk()->assertSee('<span class="tag">#php</span>', false);
        $this->assertSame(1, $failing->datasourceCalls, 'The failure is remembered for the rest of the request.');
    }

    public function test_the_datasource_is_read_once_per_request_however_many_posts_are_shown(): void
    {
        $client = $this->fakeStoryblok();

        $this->get('/')->assertOk();

        $reads = array_filter($client->requests, fn ($r) => $r['path'] === 'cdn/datasource_entries');
        $this->assertCount(1, $reads);
        $this->assertSame('tags', array_values($reads)[0]['query']['datasource']);
    }
}
