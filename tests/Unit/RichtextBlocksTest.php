<?php

namespace Tests\Unit;

use App\Blog\RichtextBlocks;
use PHPUnit\Framework\TestCase;

class RichtextBlocksTest extends TestCase
{
    public function test_reading_time_is_at_least_one_minute(): void
    {
        $this->assertSame(1, RichtextBlocks::readingMinutes([]));
        $this->assertSame(1, RichtextBlocks::readingMinutes([['type' => 'paragraph', 'text' => 'Short.']]));
    }

    public function test_reading_time_rounds_up_at_200_words_per_minute(): void
    {
        $words = fn (int $n) => ['type' => 'paragraph', 'text' => implode(' ', array_fill(0, $n, 'word'))];

        $this->assertSame(1, RichtextBlocks::readingMinutes([$words(200)]));
        $this->assertSame(2, RichtextBlocks::readingMinutes([$words(201)]));
        $this->assertSame(3, RichtextBlocks::readingMinutes([$words(250), $words(250)]));
    }

    public function test_code_blocks_do_not_count_towards_reading_time(): void
    {
        $code = ['type' => 'code', 'filename' => 'a.php', 'html' => implode(' ', array_fill(0, 1000, 'x'))];

        $this->assertSame(1, RichtextBlocks::readingMinutes([$code]));
    }
}
