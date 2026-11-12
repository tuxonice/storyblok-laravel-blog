<?php

namespace App\Blog;

use App\Storyblok\Transfers\CalloutTransfer;
use App\Storyblok\Transfers\CodeBlockTransfer;
use App\Storyblok\Transfers\ImageFigureTransfer;
use App\Storyblok\Transfers\PullQuoteTransfer;
use Illuminate\Support\Str;
use Tlab\StoryblokTransfers\Transfers\RichtextTransfer;

/**
 * TEMPORARY (step 4): flattens a richtext document into the simple block list the
 * post view renders. Inline marks, links and lists are dropped. Step 5 replaces
 * this with a real richtext renderer.
 */
class RichtextBlocks
{
    private const WORDS_PER_MINUTE = 200;

    /** @return array<int, array<string, mixed>> */
    public static function blocks(?RichtextTransfer $body): array
    {
        if ($body === null) {
            return [];
        }

        $bloks = $body->getBloks();
        $blocks = [];

        foreach ($body->getContent() as $node) {
            $type = $node['type'] ?? null;

            if ($type === 'heading') {
                $blocks[] = self::heading($node);
            } elseif ($type === 'blok') {
                foreach (self::bloks($bloks[$node['attrs']['id'] ?? ''] ?? []) as $block) {
                    $blocks[] = $block;
                }
            } elseif (($text = self::plainText($node)) !== '') {
                $blocks[] = ['type' => 'paragraph', 'text' => $text];
            }
        }

        return $blocks;
    }

    /** @param  array<int, array<string, mixed>>  $blocks */
    public static function readingMinutes(array $blocks): int
    {
        $words = 0;

        foreach ($blocks as $block) {
            if (in_array($block['type'], ['paragraph', 'heading', 'callout', 'quote'], true)) {
                $words += str_word_count($block['text']);
            }
        }

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    /** @param  array<string, mixed>  $node */
    private static function heading(array $node): array
    {
        $text = self::plainText($node);

        return ['type' => 'heading', 'level' => $node['attrs']['level'] ?? 2, 'text' => $text, 'id' => Str::slug($text)];
    }

    /** @return array<int, array<string, mixed>> */
    private static function bloks(array $components): array
    {
        $blocks = [];

        foreach ($components as $c) {
            $blocks[] = match (true) {
                $c instanceof CodeBlockTransfer => ['type' => 'code', 'filename' => (string) $c->getFilename(), 'html' => e((string) $c->getCode())],
                $c instanceof CalloutTransfer => ['type' => 'callout', 'label' => (string) $c->getLabel(), 'text' => (string) $c->getText()],
                $c instanceof PullQuoteTransfer => ['type' => 'quote', 'text' => (string) $c->getText()],
                $c instanceof ImageFigureTransfer => ['type' => 'figure', 'caption' => (string) $c->getCaption(), 'src' => $c->getImage()?->getFilename() ?: null],
                default => null,
            };
        }

        return array_values(array_filter($blocks));
    }

    private static function plainText(array $node): string
    {
        if (isset($node['text'])) {
            return $node['text'];
        }

        return trim(implode('', array_map(self::plainText(...), $node['content'] ?? [])));
    }
}
