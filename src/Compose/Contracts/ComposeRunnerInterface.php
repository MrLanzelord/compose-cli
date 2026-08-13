<?php

declare(strict_types=1);

namespace ComposeCli\Compose\Contracts;

use ComposeCli\Stacks\Service;

interface ComposeRunnerInterface
{
    /**
     * Ejecuta una acción de docker compose sobre un stack.
     *
     * @param string[] $extraArgs
     */
    public function run(Service $service, string $action, array $extraArgs = [], bool $dryRun = false): int;
}
