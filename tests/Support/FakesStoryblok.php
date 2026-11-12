<?php

namespace Tests\Support;

use App\Blog\Blog;
use Tlab\StoryblokTransfers\Content\StoryblokContent;

trait FakesStoryblok
{
    /** @param array<string, array<string, mixed>> $contentOverrides full_slug => fields merged into that story's content */
    protected function fakeStoryblok(array $contentOverrides = []): void
    {
        $this->app->instance(StoryblokContent::class, StoryblokContent::usingClient(
            new FakeContentClient($contentOverrides),
            config('storyblok.namespace'),
        ));
        $this->app->forgetInstance(Blog::class);
    }
}
