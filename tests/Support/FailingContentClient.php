<?php

namespace Tests\Support;

use Tlab\StoryblokTransfers\Client\ContentClient;
use Tlab\StoryblokTransfers\Client\ContentResponse;
use Tlab\StoryblokTransfers\Client\StoryblokApiException;

/** A Storyblok that is down: every request fails. */
class FailingContentClient implements ContentClient
{
    public const MESSAGE = 'secret internal detail: Storyblok is unreachable';

    public int $requests = 0;

    public function get(string $path, array $query): ContentResponse
    {
        $this->requests++;

        throw new StoryblokApiException(self::MESSAGE);
    }
}
