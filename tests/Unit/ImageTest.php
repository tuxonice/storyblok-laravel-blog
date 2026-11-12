<?php

namespace Tests\Unit;

use App\Blog\Image;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    public function test_url_accepts_only_absolute_http_urls(): void
    {
        $this->assertSame('https://a.example/x.jpg', Image::url('https://a.example/x.jpg'));
        $this->assertSame('http://a.example/x.jpg', Image::url(' http://a.example/x.jpg '));
        $this->assertNull(Image::url('/x.jpg'));
        $this->assertNull(Image::url('javascript:alert(1)'));
        $this->assertNull(Image::url(null));
    }

    public function test_storyblok_assets_are_resized_with_the_image_service(): void
    {
        $this->assertSame('https://a.storyblok.com/f/1/2400x2400/abc/me.jpg/m/192x192', Image::resize('https://a.storyblok.com/f/1/2400x2400/abc/me.jpg', 192, 192));
        $this->assertSame('https://a.storyblok.com/f/1/2/me.jpg/m/96x96?v=2', Image::resize('https://a.storyblok.com/f/1/2/me.jpg?v=2', 96, 96), 'The query string stays at the end.');
    }

    public function test_other_images_are_left_alone(): void
    {
        $this->assertSame('https://a.storyblok.com/f/1/2/logo.svg', Image::resize('https://a.storyblok.com/f/1/2/logo.svg', 96, 96));
        $this->assertSame('https://a.storyblok.com/f/1/2/me.jpg/m/50x50', Image::resize('https://a.storyblok.com/f/1/2/me.jpg/m/50x50', 96, 96));
        $this->assertSame('https://cdn.example.com/me.jpg', Image::resize('https://cdn.example.com/me.jpg', 96, 96));
        $this->assertSame('https://evilstoryblok.com/me.jpg', Image::resize('https://evilstoryblok.com/me.jpg', 96, 96));
        $this->assertNull(Image::resize('/relative.jpg', 96, 96));
    }
}
