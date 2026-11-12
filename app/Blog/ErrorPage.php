<?php

namespace App\Blog;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The words on an error page. Only the status code decides them: an exception's own
 * message is never shown, because it can describe internals (or a secret reason
 * passed to abort()).
 */
final class ErrorPage
{
    private const TEXT = [
        403 => ['Forbidden', 'You do not have access to this page.'],
        404 => ['Page not found', 'The page you are looking for does not exist or has moved.'],
        405 => ['Method not allowed', 'That address does not accept this kind of request.'],
        419 => ['Page expired', 'This page expired. Reload it and try again.'],
        429 => ['Too many requests', 'You are going too fast. Wait a moment and try again.'],
        500 => ['Something went wrong', 'It was our fault, and it has been logged. Please try again in a moment.'],
        503 => ['Be right back', 'The site is briefly unavailable. Please try again shortly.'],
    ];

    /** @return array{code: int, title: string, text: string} */
    public static function for(?Throwable $exception): array
    {
        $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        [$title, $text] = self::TEXT[$code] ?? [
            Response::$statusTexts[$code] ?? 'Error',
            $code < 500 ? 'That request did not work.' : 'Something went wrong on our side. Please try again in a moment.',
        ];

        return ['code' => $code, 'title' => $title, 'text' => $text];
    }
}
