<?php

declare(strict_types=1);

namespace ComposeCli\Compose;

use ComposeCli\Compose\Contracts\ComposeRunnerInterface;
use ComposeCli\Stacks\Service;
use ComposeCli\Support\Palette;

/**
 * Construye y ejecuta el comando docker compose sobre un stack.
 */
final class ComposeRunner implements ComposeRunnerInterface
{
    public function run(
        Service $service,
        string $action,
        array $extraArgs = [],
        bool $dryRun = false,
        ?string $targetService = null,
    ): int {
        $tokens = array_merge(
            ['docker', 'compose', '-f', $service->composeFile],
            explode(' ', $action),
            $extraArgs,
        );

        if ($targetService !== null && $targetService !== '') {
            $tokens[] = $targetService;
        }

        $command = implode(' ', array_map('escapeshellarg', $tokens));

        $this->announce($service, $command);

        if ($dryRun) {
            return 0;
        }

        passthru($command, $exitCode);

        return $exitCode;
    }

    private function announce(Service $service, string $command): void
    {
        echo "\n"
            . Palette::BLUE
            . Palette::BOLD
            . '▶ Ejecutando:'
            . Palette::RESET
            . ' '
            . $command
            . "\n\n";
    }
}
