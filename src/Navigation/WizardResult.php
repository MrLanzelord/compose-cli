<?php

declare(strict_types=1);

namespace ComposeCli\Navigation;

use ComposeCli\Stacks\Service;

/**
 * Resultado de la selección interactiva: stack + acción elegidos.
 */
final readonly class WizardResult
{
    public function __construct(
        public Service $service,
        public string $action,
    ) {
    }
}
