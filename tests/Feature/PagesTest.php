<?php

namespace Tests\Feature;

use Tests\TestCase;

class PagesTest extends TestCase
{
    public function test_home_renders(): void
    {
        $this->get('/')->assertOk()->assertSee('// featured', false)->assertSee('Latest posts');
    }

    public function test_post_renders(): void
    {
        $this->get('/posts/typed-content-from-storyblok')->assertOk()->assertSee('// on this page', false);
    }

    public function test_unknown_post_is_404(): void
    {
        $this->get('/posts/nope')->assertNotFound();
    }
}
