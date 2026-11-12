<?php

namespace Tests\Feature;

use App\Blog\Blog;
use Tests\Support\FakesStoryblok;
use Tests\TestCase;

class RichtextRenderingTest extends TestCase
{
    use FakesStoryblok;

    private const SLUG = 'posts/typed-content-from-storyblok';

    private function t(string $text, array $marks = []): array
    {
        return ['type' => 'text', 'text' => $text] + ($marks ? ['marks' => $marks] : []);
    }

    private function p(array ...$inline): array
    {
        return ['type' => 'paragraph', 'content' => $inline];
    }

    private function h(int $level, string $text): array
    {
        return ['type' => 'heading', 'attrs' => ['level' => $level], 'content' => [$this->t($text)]];
    }

    private function blok(array $component): array
    {
        return ['type' => 'blok', 'attrs' => ['id' => null, 'body' => [$component]]];
    }

    /** Renders a post whose body is the given nodes, through the real hydration path. */
    private function render(array $nodes): array
    {
        $this->fakeStoryblok([self::SLUG => ['body' => ['type' => 'doc', 'content' => $nodes]]]);

        return app(Blog::class)->findPost('typed-content-from-storyblok');
    }

    public function test_renders_marks_lists_and_escapes_text(): void
    {
        $post = $this->render([
            $this->p($this->t('Plain, '), $this->t('bold', [['type' => 'bold']]), $this->t(' and '), $this->t('code', [['type' => 'code']]), $this->t(' <script>alert(1)</script>')),
            ['type' => 'bullet_list', 'content' => [['type' => 'list_item', 'content' => [$this->p($this->t('one'))]], ['type' => 'list_item', 'content' => [$this->p($this->t('two'))]]]],
        ]);

        $this->assertStringContainsString('<p>Plain, <strong>bold</strong> and <code>code</code> &lt;script&gt;alert(1)&lt;/script&gt;</p>', $post['body']);
        $this->assertStringContainsString('<ul><li><p>one</p></li><li><p>two</p></li></ul>', $post['body']);
        $this->assertStringNotContainsString('<script>', $post['body']);
    }

    public function test_links_keep_safe_schemes_and_drop_unsafe_ones(): void
    {
        $link = fn (string $href, array $extra = []) => [['type' => 'link', 'attrs' => ['href' => $href] + $extra]];

        $post = $this->render([$this->p(
            $this->t('ok', $link('https://example.com/a?b=1&c=2', ['target' => '_blank'])),
            $this->t('rel', $link('/posts/other')),
            $this->t('mail', $link('me@example.com', ['linktype' => 'email'])),
            $this->t('evil', $link('javascript:alert(1)')),
            $this->t('proto', $link('//evil.example')),
        )]);

        $this->assertStringContainsString('<a href="https://example.com/a?b=1&amp;c=2" target="_blank" rel="noopener noreferrer">ok</a>', $post['body']);
        $this->assertStringContainsString('<a href="/posts/other">rel</a>', $post['body']);
        $this->assertStringContainsString('<a href="mailto:me@example.com">mail</a>', $post['body']);
        $this->assertStringNotContainsString('javascript:', $post['body']);
        $this->assertStringNotContainsString('//evil.example', $post['body']);
        $this->assertStringContainsString('evil', $post['body']); // the text survives, the link does not
    }

    public function test_headings_get_unique_anchors_and_level_two_feeds_the_index(): void
    {
        $post = $this->render([
            $this->h(2, 'Setup'),
            $this->h(3, 'Setup'),
            $this->h(2, 'Setup'),
            $this->h(2, '   '),
        ]);

        $this->assertStringContainsString('<h2 id="setup">Setup</h2>', $post['body']);
        $this->assertStringContainsString('<h3 id="setup-2">Setup</h3>', $post['body']);
        $this->assertStringContainsString('<h2 id="setup-3">Setup</h2>', $post['body']);
        $this->assertSame(['setup', 'setup-3', 'section'], array_column($post['headings'], 'id'));
    }

    public function test_embedded_blocks_render_through_blade_components(): void
    {
        $post = $this->render([
            $this->blok(['component' => 'callout', 'label' => '// heads up', 'text' => 'Mind the <gap>']),
            $this->blok(['component' => 'pull_quote', 'text' => 'Short and sharp']),
            $this->blok(['component' => 'code_block', 'filename' => 'app/Foo.php', 'language' => 'php', 'code' => "\$a = 'x';\n    return \$a;"]),
            $this->blok(['component' => 'image_figure', 'caption' => 'A caption']),
        ]);

        $html = $post['body'];
        $this->assertStringContainsString('<div class="label">// heads up</div>', $html);
        $this->assertStringContainsString('Mind the &lt;gap&gt;', $html);
        $this->assertStringContainsString('<blockquote class="pull-quote">Short and sharp</blockquote>', $html);
        $this->assertStringContainsString('<div class="strip">app/Foo.php</div>', $html);
        $this->assertStringContainsString('<figcaption>A caption</figcaption>', $html);
    }

    public function test_code_is_highlighted_with_the_design_colors_and_keeps_indentation(): void
    {
        $post = $this->render([$this->blok(['component' => 'code_block', 'filename' => 'a.php', 'language' => 'php', 'code' => "\$name = 'x';\n    echo new Foo;"])]);

        $this->assertStringContainsString('color:#7cc4ff', $post['body']); // variable
        $this->assertStringContainsString('color:#9be3a6', $post['body']); // string
        $this->assertStringContainsString("\n    ", strip_tags($post['body'])); // indentation survives
    }

    public function test_unknown_language_falls_back_to_escaped_plain_text(): void
    {
        $post = $this->render([$this->blok(['component' => 'code_block', 'filename' => 'x', 'language' => 'klingon', 'code' => '<b>&'])]);

        $this->assertStringContainsString('&lt;b&gt;&amp;', $post['body']);
        $this->assertStringNotContainsString('<b>', $post['body']);
    }

    public function test_reading_time_counts_prose_and_ignores_code(): void
    {
        $words = fn (int $n) => implode(' ', array_fill(0, $n, 'word'));

        $short = $this->render([$this->p($this->t($words(150))), $this->blok(['component' => 'code_block', 'language' => 'php', 'code' => $words(900)])]);
        $long = $this->render([$this->p($this->t($words(450)))]);

        $this->assertSame(1, $short['minutes']);
        $this->assertSame(3, $long['minutes']);
    }
}
