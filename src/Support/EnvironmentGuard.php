<?php

declare(strict_types=1);

namespace ComposeCli\Support;

/**
 * Valida el entorno de ejecución antes de arrancar la aplicación.
 */
final class EnvironmentGuard
{
    public const MIN_VERSION_ID = 80400;

    public static function check(): void
    {
        if (PHP_VERSION_ID >= self::MIN_VERSION_ID) {
            return;
        }

        fwrite(
            STDERR,
            Palette::RED
            . '❌ Error: Se requiere PHP 8.4 o superior. '
            . "Versión detectada: " . PHP_VERSION
            . Palette::RESET
            . PHP_EOL
        );
        exit(1);
    }
}
