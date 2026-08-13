<?php

declare(strict_types=1);

namespace ComposeCli\Support;

/**
 * Configuración central de la CLI.
 *
 * Parámetros configurables (en orden de precedencia):
 *   1. Variables de entorno reales.
 *   2. Archivo "compose-cli.env" junto a cli.php (config local del repo).
 *   3. Valores por defecto (directorio derivado de la ubicación del script
 *      o ~/server/docker, y sufijos de dominio integrados).
 */
final class Config
{
    public const ENV_STACKS_DIR = 'COMPOSE_CLI_STACKS_DIR';
    public const ENV_DOMAIN_SUFFIXES = 'COMPOSE_CLI_DOMAIN_SUFFIXES';
    public const CONFIG_FILE = 'compose-cli.env';

    /** @var string[] */
    private const DEFAULT_DOMAIN_SUFFIXES = [
        '.midominio.com',
    ];

    /** @var string[] */
    private array $domainSuffixes;

    /**
     * @param string[] $domainSuffixes
     */
    public function __construct(
        private readonly string $baseDir,
        array $domainSuffixes = self::DEFAULT_DOMAIN_SUFFIXES,
    ) {
        $this->domainSuffixes = $domainSuffixes;
    }

    public static function fromEnvironment(?string $scriptPath = null): self
    {
        $configDir = $scriptPath !== null ? dirname($scriptPath) : null;
        $file = $configDir !== null && is_file($configDir . '/' . self::CONFIG_FILE)
            ? self::loadEnvFile($configDir . '/' . self::CONFIG_FILE)
            : [];

        $baseDir = (string) self::firstNonEmpty([
            self::env(self::ENV_STACKS_DIR),
            $file[self::ENV_STACKS_DIR] ?? null,
            $scriptPath !== null ? dirname($scriptPath, 3) . '/docker' : null,
            (getenv('HOME') ?: '') . '/server/docker',
        ]);

        $suffixes = self::parseSuffixes(self::firstNonEmpty([
            self::env(self::ENV_DOMAIN_SUFFIXES),
            $file[self::ENV_DOMAIN_SUFFIXES] ?? null,
        ]));

        return new self($baseDir, $suffixes);
    }

    public function baseDir(): string
    {
        return $this->baseDir;
    }

    /**
     * @return string[]
     */
    public function domainSuffixes(): array
    {
        return $this->domainSuffixes;
    }

    private static function env(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<int, string|null> $values
     */
    private static function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @return array<string, string> Parser mínimo de archivos .env.
     */
    private static function loadEnvFile(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return [];
        }

        $values = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $line = preg_replace('/^export\s+/', '', $line) ?? $line;

            if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
                continue;
            }

            $value = trim($matches[2]);

            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[-1];

                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            $values[$matches[1]] = $value;
        }

        return $values;
    }

    /**
     * @return string[]
     */
    private static function parseSuffixes(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return self::DEFAULT_DOMAIN_SUFFIXES;
        }

        $suffixes = array_values(array_filter(array_map('trim', explode(',', $raw))));

        return $suffixes !== [] ? $suffixes : self::DEFAULT_DOMAIN_SUFFIXES;
    }
}
