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

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->baseDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getFilename() !== 'docker-compose.yml') {
                continue;
            }

            $relativePath = ltrim(substr($file->getPath(), strlen($this->baseDir)), '/\\');
            $parts = explode('/', str_replace('\\', '/', $relativePath));

            if (count($parts) >= 2) {
                $category = $parts[0];
                $service = implode('/', array_slice($parts, 1));
                $this->groupedServices[$category][] = $service;
            }
        }

        ksort($this->groupedServices);

        foreach ($this->groupedServices as &$services) {
            sort($services);
        }

        unset($services);
    }
}
