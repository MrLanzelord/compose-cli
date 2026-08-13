<?php

declare(strict_types=1);

namespace ComposeCli\Navigation;

/**
 * Elemento del menú: asocia una clave de retorno con su texto visible.
 */
final readonly class MenuItem
{
    public const BACK = '__BACK__';

    public function __construct(
        public string $key,
        public string $label,
    ) {
    }
}
