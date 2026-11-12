<?php

namespace Tests\Unit;

use App\Blog\SeoMeta;
use PHPUnit\Framework\TestCase;

class SeoMetaTest extends TestCase
{
    public function test_title_adds_the_site_name_unless_the_page_has_no_title(): void
    {
        $this->assertSame('Post · your-blog', SeoMeta::title('Post', 'your-blog'));
        $this->assertSame('your-blog', SeoMeta::title(null, 'your-blog'));
        $this->assertSame('your-blog', SeoMeta::title('  ', 'your-blog'));
    }

    public function test_describe_strips_tags_decodes_entities_and_collapses_whitespace(): void
    {
        $this->assertSame('Hello & "world" now', SeoMeta::describe("<p>Hello &amp; &quot;world&quot;</p>\n\n  <p>now</p>"));
        $this->assertSame('', SeoMeta::describe(null));
    }

    public function test_describe_keeps_short_text_as_it_is(): void
    {
        $text = str_repeat('a', SeoMeta::DESCRIPTION_LIMIT);

        $this->assertSame($text, SeoMeta::describe($text));
    }

    public function test_describe_cuts_long_text_at_a_word_boundary_with_an_ellipsis(): void
    {
        $text = str_repeat('lorem ipsum ', 40);

        $description = SeoMeta::describe($text);

        $this->assertLessThanOrEqual(SeoMeta::DESCRIPTION_LIMIT, mb_strlen($description));
        $this->assertStringEndsWith('…', $description);
        $this->assertMatchesRegularExpression('/(lorem|ipsum)…$/', $description, 'Must not cut a word in half.');
    }

    public function test_describe_never_splits_a_multibyte_character(): void
    {
        $description = SeoMeta::describe(str_repeat('é', 400));

        $this->assertLessThanOrEqual(SeoMeta::DESCRIPTION_LIMIT, mb_strlen($description));
        $this->assertTrue(mb_check_encoding($description, 'UTF-8'));
    }

    public function test_describe_does_not_cut_at_an_early_space_when_that_discards_most_of_the_text(): void
    {
        $text = 'Hi '.str_repeat('x', 300);

        $this->assertGreaterThan(100, mb_strlen(SeoMeta::describe($text)));
    }

    public function test_storyblok_images_are_resized_for_social_cards(): void
    {
        $this->assertSame(
            'https://a.storyblok.com/f/123/800x600/abc/img.jpg/m/1200x630',
            SeoMeta::image('https://a.storyblok.com/f/123/800x600/abc/img.jpg'),
        );
    }

    public function test_images_that_must_not_be_resized_are_left_alone(): void
    {
        $this->assertSame('https://a.storyblok.com/f/1/2/logo.svg', SeoMeta::image('https://a.storyblok.com/f/1/2/logo.svg'));
        $this->assertSame('https://a.storyblok.com/f/1/2/i.jpg/m/400x400', SeoMeta::image('https://a.storyblok.com/f/1/2/i.jpg/m/400x400'));
        $this->assertSame('https://cdn.example.com/a.jpg', SeoMeta::image('https://cdn.example.com/a.jpg'));
        $this->assertSame('https://evilstoryblok.com/a.jpg', SeoMeta::image('https://evilstoryblok.com/a.jpg'), 'Only storyblok.com and its subdomains.');
    }

    public function test_only_absolute_http_urls_are_used_as_images(): void
    {
        $this->assertNull(SeoMeta::image(null));
        $this->assertNull(SeoMeta::image(''));
        $this->assertNull(SeoMeta::image('/relative/a.jpg'));
        $this->assertNull(SeoMeta::image('javascript:alert(1)'));
        $this->assertNull(SeoMeta::image('data:image/png;base64,AAAA'));
    }

    public function test_json_ld_cannot_close_the_script_tag(): void
    {
        $meta = new SeoMeta('t', 'd', 'https://x.test/', 's', jsonLd: ['headline' => '</script><script>alert(1)</script> & "q"']);

        $json = $meta->jsonLdScript();

        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringNotContainsString('<', $json);
        $this->assertSame('</script><script>alert(1)</script> & "q"', json_decode($json, true)['headline'], 'The escapes must round-trip.');
    }

    public function test_the_twitter_card_depends_on_having_an_image(): void
    {
        $this->assertSame('summary', (new SeoMeta('t', 'd', 'c', 's'))->twitterCard());
        $this->assertSame('summary_large_image', (new SeoMeta('t', 'd', 'c', 's', image: 'https://x.test/a.jpg'))->twitterCard());
    }
}
