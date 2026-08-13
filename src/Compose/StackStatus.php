<?php

declare(strict_types=1);

namespace ComposeCli\Compose;

/**
 * Estado operativo de un stack Docker Compose.
 */
enum StackStatus: string
{
    case UP = 'up';
    case ERROR = 'error';
    case DOWN = 'down';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::UP => 'arriba',
            self::ERROR => 'con error',
            self::DOWN => 'abajo',
            self::UNKNOWN => 'desconocido',
        };
    }
}
