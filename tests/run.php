<?php

declare(strict_types=1);

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Matfire\CommonmarkPhotoswipe\PhotoswipeExtension;

require dirname(__DIR__) . '/vendor/autoload.php';

function converter(): MarkdownConverter
{
    $environment = new Environment();
    $environment->addExtension(new CommonMarkCoreExtension());
    $environment->addExtension(new PhotoswipeExtension());

    return new MarkdownConverter($environment);
}

function assertContains(string $needle, string $haystack): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException(sprintf('Failed asserting that %s contains %s', var_export($haystack, true), var_export($needle, true)));
    }
}

function assertNotContains(string $needle, string $haystack): void
{
    if (strpos($haystack, $needle) !== false) {
        throw new RuntimeException(sprintf('Failed asserting that %s does not contain %s', var_export($haystack, true), var_export($needle, true)));
    }
}

$tests = [
    'a probe exception falls back to a plain image' => static function (): void {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $html = (string) converter()->convert("![broken](/definitely-missing-image.jpg)\n\nStill rendered.");
        } finally {
            restore_error_handler();
        }

        assertContains('<img src="/definitely-missing-image.jpg"', $html);
        assertContains('Still rendered.', $html);
        assertNotContains('data-pswp-width', $html);
    },
    'a false probe result falls back to a plain image' => static function (): void {
        $html = (string) converter()->convert("![broken](/definitely-missing-image.jpg)\n\nStill rendered.");

        assertContains('<img src="/definitely-missing-image.jpg"', $html);
        assertContains('Still rendered.', $html);
        assertNotContains('data-pswp-width', $html);
    },
    'a successful image probe keeps PhotoSwipe dimensions' => static function (): void {
        $imagePath = tempnam(sys_get_temp_dir(), 'commonmark-photoswipe-');
        if ($imagePath === false) {
            throw new RuntimeException('Could not create a temporary image fixture');
        }

        try {
            file_put_contents($imagePath, base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==', true));
            $html = (string) converter()->convert(sprintf('![pixel](%s)', $imagePath));
        } finally {
            unlink($imagePath);
        }

        assertContains('data-pswp-width="1"', $html);
        assertContains('data-pswp-height="1"', $html);
    },
];

$failures = 0;

foreach ($tests as $name => $test) {
    try {
        $test();
        fwrite(STDOUT, "PASS: {$name}\n");
    } catch (Throwable $exception) {
        ++$failures;
        fwrite(STDERR, "FAIL: {$name}\n{$exception}\n");
    }
}

if ($failures > 0) {
    exit(1);
}
