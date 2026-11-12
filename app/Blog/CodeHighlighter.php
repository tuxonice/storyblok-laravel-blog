<?php

namespace App\Blog;

use Illuminate\Support\Facades\Cache;
use Phiki\Grammar\Grammar;
use Phiki\Phiki;

/**
 * Server-side syntax highlighting with the design's code colors, for both themes
 * (resources/phiki/blog-dark.json and blog-light.json). Every colored token carries
 * both colors as CSS variables (--d, --l) and the page's theme picks one, so a
 * visitor can switch theme without the code being re-rendered.
 * Returns the inner HTML for a <code> element.
 */
class CodeHighlighter
{
    private const THEMES = ['dark' => 'blog-dark', 'light' => 'blog-light'];

    /** Bump when the output format or the themes change, so cached HTML is not served stale. */
    private const CACHE_VERSION = 'v2';

    private const ALIASES = [
        'js' => 'javascript', 'ts' => 'typescript', 'sh' => 'shellscript', 'bash' => 'shellscript',
        'shell' => 'shellscript', 'zsh' => 'shellscript', 'yml' => 'yaml', 'md' => 'markdown',
        'text' => 'txt', 'plain' => 'txt', '' => 'txt',
    ];

    private ?Phiki $phiki = null;

    public function highlight(string $code, ?string $language): string
    {
        $grammar = $this->grammar($language);

        return Cache::remember(
            'highlight:'.self::CACHE_VERSION.':'.md5($grammar->value."\0".$code),
            now()->addDay(),
            fn () => $this->render($code, $grammar),
        );
    }

    private function render(string $code, Grammar $grammar): string
    {
        $code = rtrim(str_replace("\r\n", "\n", $code), "\n");
        $lines = $this->phiki()->codeToHighlightedTokens($code, $grammar, self::THEMES);

        return implode("\n", array_map(function (array $line): string {
            $html = '';

            foreach ($line as $token) {
                $text = rtrim($token->token->text, "\n");
                if ($text === '') {
                    continue;
                }

                $dark = $token->settings['dark']->foreground ?? null;
                $light = $token->settings['light']->foreground ?? null;

                $html .= $dark || $light
                    ? '<span class="hl" style="--d:'.e($dark ?? 'inherit').';--l:'.e($light ?? 'inherit').'">'.e($text).'</span>'
                    : e($text);
            }

            return $html;
        }, $lines));
    }

    private function grammar(?string $language): Grammar
    {
        $name = strtolower(trim((string) $language));

        return Grammar::tryFrom(self::ALIASES[$name] ?? $name) ?? Grammar::Txt;
    }

    private function phiki(): Phiki
    {
        return $this->phiki ??= (new Phiki)
            ->theme('blog-dark', resource_path('phiki/blog-dark.json'))
            ->theme('blog-light', resource_path('phiki/blog-light.json'));
    }
}
