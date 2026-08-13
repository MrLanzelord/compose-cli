<?php

declare(strict_types=1);

namespace ComposeCli\Support;

/**
 * Controla el modo crudo (raw) del terminal para la lectura de teclas
 * del menú interactivo. Usa flujos estándar I/O (stty + STDIN).
 */
final class Terminal
{
    public const KEY_UP = "\033[A";
    public const KEY_DOWN = "\033[B";
    public const KEY_LEFT = "\033[D";
    public const KEY_RIGHT = "\033[C";
    public const KEY_ENTER = "\n";
    public const KEY_RETURN = "\r";

    private bool $rawMode = false;

    public function enableRawMode(): void
    {
        system('stty -echo -icanon min 1 time 0');
        $this->rawMode = true;
    }

    public function restore(): void
    {
        if ($this->rawMode) {
            system('stty sane');
            $this->rawMode = false;
        }
    }

    public function readKey(): string
    {
        $key = fread(STDIN, 1);

        if ($key === "\033") {
            $key .= fread(STDIN, 2);
        }

        return $key;
    }

    public function isInteractive(): bool
    {
        return function_exists('stream_isatty')
            && stream_isatty(STDIN)
            && stream_isatty(STDOUT);
    }
}
