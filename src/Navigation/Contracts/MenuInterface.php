<?php

declare(strict_types=1);

namespace ComposeCli\Navigation\Contracts;

use ComposeCli\Navigation\MenuItem;

/**
 * Motor de selección de menú. No utiliza los componentes de menú nativos
 * de symfony/console: implementa una solución híbrida sobre flujos I/O
 * estándar (stty + STDIN) para las flechas, con respaldo no-interactivo.
 */
interface MenuInterface
{
    /**
     * @param MenuItem[] $items
     *
     * @return string|null Clave del elemento seleccionado, o null si se
     *                     navegó hacia atrás.
     */
    public function select(array $items, string $title, bool $allowBack = false): ?string;
}
