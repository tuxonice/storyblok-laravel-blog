<?php

namespace App\Blog;

use App\Storyblok\Transfers\CalloutTransfer;
use App\Storyblok\Transfers\CodeBlockTransfer;
use App\Storyblok\Transfers\ImageFigureTransfer;
use App\Storyblok\Transfers\PullQuoteTransfer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Tlab\StoryblokTransfers\Transfers\RichtextTransfer;

/**
 * Renders a Storyblok richtext document to HTML.
 *
 * Plain nodes are rendered here; components embedded in the text are looked up
 * in RichtextTransfer::getBloks() by the node's id and rendered through the
 * Blade components in resources/views/components/blocks. Headings get unique
 * anchor ids, and level-2 headings are collected for the "on this page" index.
 *
 * Everything that comes from the document is escaped. Unknown node types render
 * their children, unknown marks are ignored, and unsafe link schemes are dropped.
 */
class RichtextRenderer
{
    /** @var array<string, array<int, object>> */
    private array $bloks = [];

    /** @var array<string, true> */
    private array $usedIds = [];

    /** @var array<int, array{id: string, text: string}> */
    private array $headings = [];

    private int $words = 0;

    public function __construct(private readonly CodeHighlighter $highlighter) {}

    public function render(?RichtextTransfer $body): RenderedRichtext
    {
        if ($body === null) {
            return new RenderedRichtext;
        }

        $this->bloks = $body->getBloks();
        $this->usedIds = [];
        $this->headings = [];
        $this->words = 0;

        $html = $this->nodes($body->getContent());

        return new RenderedRichtext($html, $this->headings, $this->words);
    }

    /** @param  array<int, array<string, mixed>>  $nodes */
    private function nodes(array $nodes): string
    {
        return implode('', array_map($this->node(...), $nodes));
    }

    /** @param  array<string, mixed>  $node */
    private function node(array $node): string
    {
        $children = fn () => $this->nodes($node['content'] ?? []);

        return match ($node['type'] ?? null) {
            'paragraph' => ($inner = $children()) === '' ? '' : "<p>{$inner}</p>",
            'heading' => $this->heading($node),
            'bullet_list' => '<ul>'.$children().'</ul>',
            'ordered_list' => '<ol>'.$children().'</ol>',
            'list_item' => '<li>'.$children().'</li>',
            'blockquote' => '<blockquote>'.$children().'</blockquote>',
            'code_block' => $this->nativeCode($node),
            'horizontal_rule' => '<hr>',
            'hard_break' => '<br>',
            'image' => $this->image($node),
            'text' => $this->text($node),
            'blok' => $this->blok($node),
            default => $children(),
        };
    }

    /** @param  array<string, mixed>  $node */
    private function heading(array $node): string
    {
        $level = min(6, max(2, (int) ($node['attrs']['level'] ?? 2)));
        $text = $this->plainText($node);
        $id = $this->uniqueId($text);

        if ($level === 2) {
            $this->headings[] = ['id' => $id, 'text' => $text];
        }

        return "<h{$level} id=\"".e($id).'">'.$this->nodes($node['content'] ?? [])."</h{$level}>";
    }

    /** @param  array<string, mixed>  $node */
    private function text(array $node): string
    {
        $text = (string) ($node['text'] ?? '');
        $this->words += preg_match_all("/[\p{L}\p{N}][\p{L}\p{N}'’-]*/u", $text);

        $html = e($text);

        foreach ($node['marks'] ?? [] as $mark) {
            $html = match ($mark['type'] ?? null) {
                'bold' => "<strong>{$html}</strong>",
                'italic' => "<em>{$html}</em>",
                'strike' => "<s>{$html}</s>",
                'underline' => "<u>{$html}</u>",
                'code' => "<code>{$html}</code>",
                'superscript' => "<sup>{$html}</sup>",
                'subscript' => "<sub>{$html}</sub>",
                'highlight' => "<mark>{$html}</mark>",
                'link' => $this->link($mark['attrs'] ?? [], $html),
                default => $html,
            };
        }

        return $html;
    }

    /** @param  array<string, mixed>  $attrs */
    private function link(array $attrs, string $inner): string
    {
        $href = $this->safeHref((string) ($attrs['href'] ?? ''), (string) ($attrs['linktype'] ?? ''));

        if ($href === null) {
            return $inner;
        }

        $external = ($attrs['target'] ?? null) === '_blank';

        return '<a href="'.e($href).'"'.($external ? ' target="_blank" rel="noopener noreferrer"' : '').">{$inner}</a>";
    }

    private function safeHref(string $href, string $linktype): ?string
    {
        $href = trim($href);

        if ($href === '') {
            return null;
        }

        if ($linktype === 'email' && ! str_starts_with($href, 'mailto:')) {
            $href = 'mailto:'.$href;
        }

        // Relative paths and anchors, or an explicit safe scheme. Everything else
        // (javascript:, data:, vbscript:, protocol-relative //host) is dropped.
        if (preg_match('~^(?:/(?!/)|#)~', $href) || preg_match('~^(?:https?|mailto|tel):~i', $href)) {
            return $href;
        }

        return null;
    }

    /** @param  array<string, mixed>  $node */
    private function image(array $node): string
    {
        $src = (string) ($node['attrs']['src'] ?? '');

        if (! preg_match('~^https?://|^/~i', $src)) {
            return '';
        }

        $alt = e((string) ($node['attrs']['alt'] ?? ''));

        return '<img class="img-fluid rounded" loading="lazy" src="'.e($src)."\" alt=\"{$alt}\">";
    }

    /** @param  array<string, mixed>  $node */
    private function nativeCode(array $node): string
    {
        $language = preg_replace('/^language-/', '', (string) ($node['attrs']['class'] ?? ''));

        return $this->codeBlock('', $language, $this->plainText($node, keepWhitespace: true));
    }

    /** @param  array<string, mixed>  $node */
    private function blok(array $node): string
    {
        $html = '';

        foreach ($this->bloks[$node['attrs']['id'] ?? ''] ?? [] as $component) {
            $html .= match (true) {
                $component instanceof CodeBlockTransfer => $this->codeBlock((string) $component->getFilename(), (string) $component->getLanguage(), (string) $component->getCode()),
                $component instanceof CalloutTransfer => $this->callout((string) $component->getLabel(), (string) $component->getText()),
                $component instanceof PullQuoteTransfer => $this->pullQuote((string) $component->getText()),
                $component instanceof ImageFigureTransfer => $this->figure((string) $component->getCaption(), $component->getImage()?->getFilename()),
                default => '', // a component without a Blade view is skipped, never fatal
            };
        }

        return $html;
    }

    private function codeBlock(string $filename, ?string $language, string $code): string
    {
        return Blade::render('<x-blocks.code_block :filename="$filename" :html="$html" />', [
            'filename' => $filename,
            'html' => $this->highlighter->highlight($code, $language),
        ]);
    }

    private function callout(string $label, string $text): string
    {
        $this->countWords($text);

        return Blade::render('<x-blocks.callout :label="$label" :text="$text" />', compact('label', 'text'));
    }

    private function pullQuote(string $text): string
    {
        $this->countWords($text);

        return Blade::render('<x-blocks.pull_quote :text="$text" />', compact('text'));
    }

    private function figure(string $caption, ?string $src): string
    {
        $src = $src && preg_match('~^https?://~i', $src) ? $src : null;

        return Blade::render('<x-blocks.image_figure :caption="$caption" :src="$src" />', compact('caption', 'src'));
    }

    private function countWords(string $text): void
    {
        $this->words += preg_match_all("/[\p{L}\p{N}][\p{L}\p{N}'’-]*/u", $text);
    }

    /** @param  array<string, mixed>  $node */
    private function plainText(array $node, bool $keepWhitespace = false): string
    {
        if (isset($node['text'])) {
            return (string) $node['text'];
        }

        $text = implode('', array_map(fn (array $child) => $this->plainText($child, $keepWhitespace), $node['content'] ?? []));

        return $keepWhitespace ? $text : trim($text);
    }

    private function uniqueId(string $text): string
    {
        $base = Str::slug($text) ?: 'section';
        $id = $base;

        for ($n = 2; isset($this->usedIds[$id]); $n++) {
            $id = "{$base}-{$n}";
        }

        return $this->usedIds[$id] = $id;
    }
}
