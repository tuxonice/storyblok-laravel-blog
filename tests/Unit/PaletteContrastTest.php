<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * CLAUDE.md: "Text contrast is at least 4.5:1 on the background. Keep it that way if you
 * change shades." This reads the palette from resources/scss/app.scss, so changing a
 * shade there is checked here.
 */
class PaletteContrastTest extends TestCase
{
    /** @var array<string, string> */
    private array $scss;

    protected function setUp(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/resources/scss/app.scss');
        preg_match_all('/^\$([a-z-]+):\s*(#[0-9a-fA-F]{6});/m', $source, $m);

        $this->scss = array_combine($m[1], $m[2]);
    }

    private function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(
            fn (string $c) => ($v = hexdec($c) / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4,
            str_split(ltrim($hex, '#'), 2),
        );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private function contrast(string $a, string $b): float
    {
        [$hi, $lo] = [max($this->luminance($a), $this->luminance($b)), min($this->luminance($a), $this->luminance($b))];

        return ($hi + 0.05) / ($lo + 0.05);
    }

    /** @return array<string, array{0: array<string, string>}> */
    public static function themes(): array
    {
        return [
            'dark' => [[
                'page' => 'page-bg', 'surface' => 'surface', 'code' => 'code-bg',
                'text' => 'text', 'body' => 'text-body', 'muted' => 'muted', 'disabled' => 'disabled', 'accent' => 'accent',
                'var' => 'code-var', 'class' => 'code-class', 'string' => 'code-string',
            ]],
            'light' => [[
                'page' => 'light-page', 'surface' => 'light-surface', 'code' => 'light-code-bg',
                'text' => 'light-text', 'body' => 'light-text-body', 'muted' => 'light-muted', 'disabled' => 'light-disabled', 'accent' => 'light-accent',
                'var' => 'light-code-var', 'class' => 'light-code-class', 'string' => 'light-code-string',
            ]],
        ];
    }

    /** @dataProvider themes */
    #[DataProvider('themes')]
    public function test_text_colors_reach_4_5_to_1_on_the_page_and_surface(array $names): void
    {
        foreach (['text', 'body', 'muted', 'disabled', 'accent'] as $token) {
            foreach (['page', 'surface'] as $background) {
                $ratio = $this->contrast($this->scss[$names[$token]], $this->scss[$names[$background]]);

                $this->assertGreaterThanOrEqual(4.5, $ratio, sprintf('%s on %s is %.2f:1', $names[$token], $names[$background], $ratio));
            }
        }
    }

    /** @dataProvider themes */
    #[DataProvider('themes')]
    public function test_code_colors_reach_4_5_to_1_on_the_code_background(array $names): void
    {
        foreach (['body', 'var', 'class', 'string', 'muted'] as $token) {
            $ratio = $this->contrast($this->scss[$names[$token]], $this->scss[$names['code']]);

            $this->assertGreaterThanOrEqual(4.5, $ratio, sprintf('%s on %s is %.2f:1', $names[$token], $names['code'], $ratio));
        }
    }

    public function test_the_amber_button_text_is_readable_in_both_themes(): void
    {
        $this->assertGreaterThanOrEqual(4.5, $this->contrast($this->scss['ink'], $this->scss['accent']));
    }

    public function test_the_highlighter_themes_use_the_same_colors_as_the_stylesheet(): void
    {
        $root = dirname(__DIR__, 2).'/resources/phiki/';

        foreach (['dark' => ['code-var', 'code-class', 'code-string'], 'light' => ['light-code-var', 'light-code-class', 'light-code-string']] as $theme => $tokens) {
            $json = json_decode(file_get_contents($root."blog-{$theme}.json"), true);
            $used = array_map('strtolower', array_column(array_column($json['tokenColors'], 'settings'), 'foreground'));

            foreach ($tokens as $token) {
                $this->assertContains(strtolower($this->scss[$token]), $used, "{$token} is not used by the {$theme} highlighter theme");
            }
        }
    }
}
