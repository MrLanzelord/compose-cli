<?php

declare(strict_types=1);

namespace ComposeCli\Compose;

/**
 * Acciones disponibles sobre un stack Docker Compose.
 */
enum ComposeAction: string
{
    case UP = 'up';
    case UP_D = 'up -d';
    case UP_BUILD = 'up --build';
    case UP_D_BUILD = 'up -d --build';
    case DOWN = 'down';
    case STOP = 'stop';
    case LOGS = 'logs';
    case LOGS_FOLLOW = 'logs -f';
    case RESTART = 'restart';

    /**
     * Subcomando docker compose (primer token).
     */
    public function command(): string
    {
        return explode(' ', $this->value)[0];
    }

    /**
     * Argumentos adicionales del subcomando.
     *
     * @return string[]
     */
    public function arguments(): array
    {
        return array_slice(explode(' ', $this->value), 1);
    }

    public function label(): string
    {
        return $this->value;
    }
}
