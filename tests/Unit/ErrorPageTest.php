<?php

namespace Tests\Unit;

use App\Blog\ErrorPage;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorPageTest extends TestCase
{
    public function test_it_has_words_for_the_common_statuses(): void
    {
        $this->assertSame(['code' => 404, 'title' => 'Page not found', 'text' => 'The page you are looking for does not exist or has moved.'], ErrorPage::for(new HttpException(404)));
        $this->assertSame('Forbidden', ErrorPage::for(new HttpException(403))['title']);
        $this->assertSame('Method not allowed', ErrorPage::for(new HttpException(405))['title']);
        $this->assertSame('Too many requests', ErrorPage::for(new HttpException(429))['title']);
        $this->assertSame('Be right back', ErrorPage::for(new HttpException(503))['title']);
    }

    public function test_other_statuses_use_the_standard_status_text(): void
    {
        $this->assertSame('Gone', ErrorPage::for(new HttpException(410))['title']);
        $this->assertSame('That request did not work.', ErrorPage::for(new HttpException(410))['text']);
        $this->assertSame('Bad Gateway', ErrorPage::for(new HttpException(502))['title']);
        $this->assertSame('Error', ErrorPage::for(new HttpException(599))['title']);
    }

    public function test_an_exception_that_is_not_an_http_error_is_a_500(): void
    {
        $this->assertSame(500, ErrorPage::for(new RuntimeException('boom'))['code']);
        $this->assertSame(500, ErrorPage::for(null)['code']);
    }

    public function test_the_exception_message_is_never_part_of_the_page(): void
    {
        $page = ErrorPage::for(new HttpException(403, 'the secret reason'));

        $this->assertStringNotContainsString('secret', json_encode($page));
    }
}
