<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression protection for Packagist / GitHub metadata (v0.1.1 audit).
 */
final class ComposerMetadataTest extends TestCase
{
    public function test_composer_json_points_at_correct_github_repository(): void
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'composer.json';
        self::assertFileExists($path, 'Root composer.json must exist.');

        $raw = file_get_contents($path);
        self::assertNotFalse($raw, 'Unable to read composer.json.');

        /** @var array{name?: string, homepage?: string, support?: array{issues?: string, source?: string}} $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(
            'muradyanvano/php-lexorank',
            $data['name'] ?? null,
            'Composer package name must remain muradyanvano/php-lexorank.',
        );

        $homepage = $data['homepage'] ?? '';
        $issues = $data['support']['issues'] ?? '';
        $source = $data['support']['source'] ?? '';

        foreach (['homepage' => $homepage, 'support.issues' => $issues, 'support.source' => $source] as $field => $url) {
            self::assertStringContainsString(
                'github.com/muradyanvano/php-lexorank',
                $url,
                sprintf('%s must reference github.com/muradyanvano/php-lexorank, got: %s', $field, $url),
            );
            self::assertStringNotContainsString(
                'github.com/muradyanvano1995/php-lexorank',
                $url,
                sprintf('%s must not use the old github.com/muradyanvano1995/php-lexorank path.', $field),
            );
        }
    }
}
