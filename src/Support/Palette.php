<?php

declare(strict_types=1);

namespace ComposeCli\Support;

/**
 * Paleta de colores ANSI compartida por toda la CLI.
 */
final class Palette
{
    public const RESET = "\033[0m";
    public const BOLD = "\033[1m";
    public const DIM = "\033[90m";
    public const GREEN = "\033[1;32m";
    public const CYAN = "\033[1;36m";
    public const YELLOW = "\033[1;33m";
    public const RED = "\033[1;31m";
    public const PURPLE = "\033[1;35m";
    public const BLUE = "\033[1;34m";
    public const WHITE = "\033[1;37m";
}
