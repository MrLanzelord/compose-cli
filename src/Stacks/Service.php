<?php

declare(strict_types=1);

namespace ComposeCli\Stacks;

/**
 * Servicio (stack) Docker Compose dentro de una categoría.
 */
final readonly class Service
{
    public function __construct(
        public string $category,
        public string $name,
        public string $composeFile,
        public string $displayName,
    ) {
    }

    public function key(): string
    {
        return $this->category . '/' . $this->name;
    }
}
