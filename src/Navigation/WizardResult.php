<?php

declare(strict_types=1);

namespace ComposeCli\Navigation;

use ComposeCli\Stacks\Service;

/**
 * Resultado de la selección interactiva: stack + acción elegidos, y
 * opcionalmente el servicio concreto del stack sobre el que actuar.
 */
final readonly class WizardResult
{
    public function __construct(
        public Service $service,
        public string $action,
        public ?string $targetService = null,
    ) {
    }
}
