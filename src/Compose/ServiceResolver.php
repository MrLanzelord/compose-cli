<?php

declare(strict_types=1);

namespace ComposeCli\Compose;

/**
 * Enumera los servicios (claves de `services:`) de un stack Docker Compose
 * usando el propio docker (`docker compose config --services`). Evita añadir
 * un parser YAML y aprovecha la interpolación del `.env` que ya hace docker.
 *
 * Los servicios con `profiles` no aparecen en `config --services` por defecto;
 * se documenta como limitación conocida.
 */
final class ServiceResolver
{
    /** @var array<string, string[]> */
    private array $cache = [];

    /**
     * @return string[] Nombres de servicio del stack, o [] si no se pudo resolver.
     */
    public function services(string $composeFile): array
    {
        if (array_key_exists($composeFile, $this->cache)) {
            return $this->cache[$composeFile];
        }

        $out = [];
        $command = 'docker compose -f ' . escapeshellarg($composeFile)
            . ' config --services 2>/dev/null';

        exec($command, $out, $code);

        if ($code !== 0) {
            return $this->cache[$composeFile] = [];
        }

        $services = array_values(array_filter(array_map('trim', $out)));

        return $this->cache[$composeFile] = $services;
    }
}
