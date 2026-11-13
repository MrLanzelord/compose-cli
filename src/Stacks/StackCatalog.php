<?php

declare(strict_types=1);

namespace ComposeCli\Stacks;

use ComposeCli\Stacks\Contracts\StackCatalogInterface;
use ComposeCli\Support\DisplayNames;

/**
 * Escanea el directorio base y agrupa los stacks por categoría,
 * manteniendo el orden y la detección de docker-compose.yml.
 */
final class StackCatalog implements StackCatalogInterface
{
    use DisplayNames;

    /** @var array<string, string[]> */
    private array $groupedServices = [];

    public function __construct(
        private readonly string $baseDir,
        private readonly array $domainSuffixes,
    ) {
        $this->scan();
    }

    public function groupedServices(): array
    {
        return $this->groupedServices;
    }

    public function categories(): array
    {
        return array_keys($this->groupedServices);
    }

    public function servicesOf(string $category): array
    {
        $services = [];

        foreach ($this->groupedServices[$category] ?? [] as $name) {
            $services[] = $this->service($category, $name);
        }

        return $services;
    }

    public function resolve(string $input): ?Service
    {
        $parts = explode('/', $input);

        if (count($parts) === 2) {
            [$category, $name] = $parts;

            return isset($this->groupedServices[$category])
                && in_array($name, $this->groupedServices[$category], true)
                    ? $this->service($category, $name)
                    : null;
        }

        foreach ($this->groupedServices as $category => $services) {
            if (in_array($input, $services, true)) {
                return $this->service($category, $input);
            }
        }

        return null;
    }

    /**
     * Resuelve la entrada del modo directo: primero por nombre exacto y, si no
     * coincide, por el nombre limpio (sin sufijo de dominio). Devuelve null si
     * no hay coincidencia o si hay varias (ambiguo).
     */
    public function resolveForInput(string $input): ?Service
    {
        $exact = $this->resolve($input);

        if ($exact !== null) {
            return $exact;
        }

        $matches = $this->candidates($input);

        return count($matches) === 1 ? $matches[0] : null;
    }

    /**
     * Todos los stacks cuyo nombre limpio coincide con la entrada (para
     * detectar ambigüedades y mostrarlas al usuario).
     *
     * @return Service[]
     */
    public function candidates(string $input): array
    {
        $parts = explode('/', $input);
        $categoryFilter = count($parts) === 2 ? $parts[0] : null;
        $displayFilter = count($parts) === 2 ? $parts[1] : $input;

        $matches = [];

        foreach ($this->groupedServices as $category => $services) {
            if ($categoryFilter !== null && $category !== $categoryFilter) {
                continue;
            }

            foreach ($services as $name) {
                if ($this->displayName($name, $this->domainSuffixes) === $displayFilter) {
                    $matches[] = $this->service($category, $name);
                }
            }
        }

        return $matches;
    }

    private function service(string $category, string $name): Service
    {
        return new Service(
            category: $category,
            name: $name,
            composeFile: $this->baseDir . '/' . $category . '/' . $name . '/docker-compose.yml',
            displayName: $this->displayName($name, $this->domainSuffixes),
        );
    }

    private function scan(): void
    {
        if (!is_dir($this->baseDir)) {
            $this->groupedServices = [];

            return;
        }

        // Escaneo NO recursivo de 2 niveles: <categoria>/<servicio>/docker-compose.yml.
        // Evita descender a árboles de datos (db/, web/, vendor/, ...) que pueden
        // tener permisos de contenedor (crash por Permission denied) y capturar
        // docker-compose.yml anidados que no son stacks reales.
        foreach ($this->directoriesIn($this->baseDir) as $category) {
            foreach ($this->directoriesIn($this->baseDir . '/' . $category) as $service) {
                if (is_file($this->baseDir . '/' . $category . '/' . $service . '/docker-compose.yml')) {
                    $this->groupedServices[$category][] = $service;
                }
            }
        }

        ksort($this->groupedServices);

        foreach ($this->groupedServices as &$services) {
            sort($services);
        }

        unset($services);
    }

    /**
     * @return string[] Nombres de subdirectorios (no ocultos) de una carpeta,
     *                  ordenados alfabéticamente.
     */
    private function directoriesIn(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $entries = scandir($path);

        if ($entries === false) {
            return [];
        }

        $dirs = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
                continue;
            }

            if (is_dir($path . '/' . $entry)) {
                $dirs[] = $entry;
            }
        }

        sort($dirs);

        return $dirs;
    }
}
