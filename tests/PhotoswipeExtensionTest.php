<?php

declare(strict_types=1);

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Matfire\CommonmarkPhotoswipe\PhotoswipeExtension;

function converter(): MarkdownConverter
{
    $environment = new Environment();
    $environment->addExtension(new CommonMarkCoreExtension());
    $environment->addExtension(new PhotoswipeExtension());

    return new MarkdownConverter($environment);
}

it('falls back to a plain image when the probe throws', function (): void {
    set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    try {
        $html = (string) converter()->convert("![broken](/definitely-missing-image.jpg)\n\nStill rendered.");
    } finally {
        restore_error_handler();
    }

    expect($html)
        ->toContain('<img src="/definitely-missing-image.jpg"')
        ->toContain('Still rendered.')
        ->not->toContain('data-pswp-width');
});

it('falls back to a plain image when the probe returns false', function (): void {
    $imagePath = tempnam(sys_get_temp_dir(), 'commonmark-photoswipe-');
    expect($imagePath)->not->toBeFalse();

    try {
        file_put_contents($imagePath, 'not an image');
        $html = (string) converter()->convert(sprintf("![broken](%s)\n\nStill rendered.", $imagePath));
    } finally {
        unlink($imagePath);
    }

    expect($html)
        ->toContain(sprintf('<img src="%s"', $imagePath))
        ->toContain('Still rendered.')
        ->not->toContain('data-pswp-width');
});

it('adds PhotoSwipe dimensions when the probe succeeds', function (): void {
    $imagePath = tempnam(sys_get_temp_dir(), 'commonmark-photoswipe-');
    expect($imagePath)->not->toBeFalse();

    try {
        file_put_contents($imagePath, base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==', true));
        $html = (string) converter()->convert(sprintf('![pixel](%s)', $imagePath));
    } finally {
        unlink($imagePath);
    }

    expect($html)
        ->toContain('data-pswp-width="1"')
        ->toContain('data-pswp-height="1"');
});
