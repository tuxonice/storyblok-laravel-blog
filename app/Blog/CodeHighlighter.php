<?php

namespace App\Blog;

use Illuminate\Support\Facades\Cache;
use Phiki\Grammar\Grammar;
use Phiki\Phiki;

/**
 * Server-side syntax highlighting with the design's code colors
 * (resources/phiki/blog-dark.json). Returns the inner HTML for a <code> element.
 */
class CodeHighlighter
{
    private const THEME = 'blog-dark';

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
            'highlight:'.md5($grammar->value."\0".$code),
            now()->addDay(),
            fn () => $this->render($code, $grammar),
        );
    }

    private function render(string $code, Grammar $grammar): string
    {
        $code = rtrim(str_replace("\r\n", "\n", $code), "\n");
        $lines = $this->phiki()->codeToHighlightedTokens($code, $grammar, self::THEME);

        return implode("\n", array_map(function (array $line): string {
            $html = '';

            foreach ($line as $token) {
                $text = rtrim($token->token->text, "\n");
                if ($text === '') {
                    continue;
                }

                $key = array_key_first($token->settings);
                $color = $key === null ? null : $token->settings[$key]->foreground;
                $html .= $color ? '<span style="color:'.e($color).'">'.e($text).'</span>' : e($text);
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
        return $this->phiki ??= (new Phiki)->theme(self::THEME, resource_path('phiki/blog-dark.json'));
    }
}
