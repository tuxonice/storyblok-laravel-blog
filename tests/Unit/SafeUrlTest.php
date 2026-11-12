<?php

namespace Tests\Unit;

use App\Blog\SafeUrl;
use PHPUnit\Framework\TestCase;

class SafeUrlTest extends TestCase
{
    public function test_it_keeps_the_shapes_a_link_may_have(): void
    {
        $this->assertSame('https://example.com/a?b=1', SafeUrl::href('https://example.com/a?b=1'));
        $this->assertSame('HTTP://EXAMPLE.COM', SafeUrl::href('HTTP://EXAMPLE.COM'));
        $this->assertSame('/posts/x', SafeUrl::href('/posts/x'));
        $this->assertSame('#section', SafeUrl::href('#section'));
        $this->assertSame('mailto:me@example.com', SafeUrl::href('mailto:me@example.com'));
        $this->assertSame('tel:+4915112345678', SafeUrl::href('tel:+4915112345678'));
        $this->assertSame('  /trimmed  ' === '' ? null : '/trimmed', SafeUrl::href('  /trimmed  '));
    }

    public function test_an_email_address_becomes_a_mailto_link(): void
    {
        $this->assertSame('mailto:me@example.com', SafeUrl::href('me@example.com', 'email'));
        $this->assertSame('mailto:me@example.com', SafeUrl::href('mailto:me@example.com', 'email'));
    }

    public function test_it_drops_everything_else(): void
    {
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', ' javascript:alert(1)', 'data:text/html;base64,AAAA', 'vbscript:x', '//evil.example/x', 'file:///etc/passwd', 'example.com', '', '   ', null] as $bad) {
            $this->assertNull(SafeUrl::href($bad), var_export($bad, true));
        }
    }
}
