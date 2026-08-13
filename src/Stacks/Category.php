<?php

declare(strict_types=1);

namespace ComposeCli\Stacks;

/**
 * Categoría de stacks (ai, apps, core, instances, sites).
 */
final readonly class Category
{
    public function __construct(
        public string $name,
        /** @var Service[] */
        public array $services = [],
    ) {
    }
}
