<?php

namespace App\Blog;

/** The result of rendering a richtext field. */
final readonly class RenderedRichtext
{
    /**
     * @param  string  $html  Safe to output unescaped: every text node and attribute is escaped.
     * @param  array<int, array{id: string, text: string}>  $headings  Level-2 headings, for the "on this page" index.
     * @param  int  $words  Words of prose (code blocks excluded), for the reading time.
     */
    public function __construct(
        public string $html = '',
        public array $headings = [],
        public int $words = 0,
    ) {}

    public function readingMinutes(int $wordsPerMinute = 200): int
    {
        return max(1, (int) ceil($this->words / $wordsPerMinute));
    }
}
