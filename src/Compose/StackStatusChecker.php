<?php

declare(strict_types=1);

namespace ComposeCli\Compose;

/**
 * Determina el estado operativo de cada stack con una única consulta a
 * Docker por sesión: docker ps -aq (filtrado por el label de compose) más
 * docker inspect de los contenedores resultantes, agrupados por el label
 * com.docker.compose.project.config_files.
 */
final class StackStatusChecker
{
    private const LABEL_FILTER = 'label=com.docker.compose.project.config_files';

    /** @var array<string, StackStatus>|null */
    private ?array $cache = null;

    private bool $loaded = false;

    private bool $dockerAvailable = true;

    public function status(string $composeFile): StackStatus
    {
        $this->load();

        if (!$this->dockerAvailable) {
            return StackStatus::UNKNOWN;
        }

        $path = realpath($composeFile) ?: $composeFile;

        return $this->cache[$path] ?? StackStatus::DOWN;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;
        $this->cache = $this->fetch();

        if ($this->cache === null) {
            $this->dockerAvailable = false;
            $this->cache = [];
        }
    }

    /**
     * @return array<string, StackStatus>|null realpath(compose) => estado, o
     *                                         null si Docker no está disponible.
     */
    private function fetch(): ?array
    {
        $ids = $this->listContainerIds();

        if ($ids === null) {
            return null;
        }

        $map = [];

        foreach ($this->inspect($ids) ?? [] as $file => $containerStates) {
            $map[$file] = $this->aggregate($containerStates);
        }

        return $map;
    }

    /**
     * @return string[]|null IDs de contenedores de stacks Compose, o null
     *                      si Docker falló.
     */
    private function listContainerIds(): ?array
    {
        $out = [];
        $command = 'docker ps -aq --filter '
            . escapeshellarg(self::LABEL_FILTER)
            . ' 2>/dev/null';

        exec($command, $out, $code);

        if ($code !== 0) {
            return null;
        }

        return array_values(array_filter(array_map('trim', $out)));
    }

    /**
     * @param string[] $ids
     *
     * @return array<string, StackStatus[]>|null realpath(compose) => estados
     *                                           por contenedor, o null si falló.
     */
    private function inspect(array $ids): ?array
    {
        if ($ids === []) {
            return [];
        }

        $out = [];
        $command = 'docker inspect --format '
            . escapeshellarg('{{json .}}')
            . ' '
            . implode(' ', array_map('escapeshellarg', $ids))
            . ' 2>/dev/null';

        exec($command, $out, $code);

        if ($code !== 0) {
            return null;
        }

        $states = [];

        foreach ($out as $line) {
            $container = json_decode($line, true);

            $file = $container['Config']['Labels']['com.docker.compose.project.config_files'] ?? null;

            if (!is_array($container) || !is_string($file)) {
                continue;
            }

            $real = realpath($file) ?: $file;
            $states[$real][] = $this->classify($container);
        }

        return $states;
    }

    private function classify(array $container): StackStatus
    {
        $state = (string) ($container['State']['Status'] ?? '');
        $exitCode = (int) ($container['State']['ExitCode'] ?? 0);
        $health = (string) ($container['State']['Health']['Status'] ?? 'none');

        return match (true) {
            $state === 'running' && $health === 'unhealthy' => StackStatus::ERROR,
            $state === 'running' => StackStatus::UP,
            in_array($state, ['dead', 'restarting'], true) => StackStatus::ERROR,
            $state === 'exited' && $exitCode !== 0 => StackStatus::ERROR,
            default => StackStatus::DOWN,
        };
    }

    /**
     * @param StackStatus[] $containerStates
     */
    private function aggregate(array $containerStates): StackStatus
    {
        $error = false;
        $up = false;

        foreach ($containerStates as $state) {
            $error = $error || $state === StackStatus::ERROR;
            $up = $up || $state === StackStatus::UP;
        }

        if ($error) {
            return StackStatus::ERROR;
        }

        return $up ? StackStatus::UP : StackStatus::DOWN;
    }
}
