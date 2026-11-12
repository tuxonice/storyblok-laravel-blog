<?php

namespace Tests\Feature;

use Tests\Support\FakesStoryblok;
use Tests\TestCase;

class ThemeToggleTest extends TestCase
{
    use FakesStoryblok;

    public function test_dark_is_the_default_theme(): void
    {
        $this->fakeStoryblok();

        $this->get('/')->assertSee('<html lang="en" data-bs-theme="dark">', false);
    }

    public function test_the_toggle_is_enabled_and_labelled(): void
    {
        $this->fakeStoryblok();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-theme-toggle', $html);
        $this->assertStringContainsString('aria-label="Switch to light theme"', $html);
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*data-theme-toggle[^>]*\bdisabled\b/', $html);
    }

    public function test_the_saved_theme_is_applied_before_the_stylesheet_loads(): void
    {
        $this->fakeStoryblok();

        $html = $this->get('/')->assertOk()->getContent();

        $script = strpos($html, "localStorage.getItem('theme')");
        $stylesheet = strpos($html, 'rel="stylesheet"');

        $this->assertNotFalse($script);
        $this->assertNotFalse($stylesheet);
        $this->assertLessThan($stylesheet, $script, 'The theme script must run before the CSS so a saved light theme never flashes dark.');
    }
}
