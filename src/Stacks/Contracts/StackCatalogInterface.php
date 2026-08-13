<?php

declare(strict_types=1);

namespace ComposeCli\Stacks\Contracts;

use ComposeCli\Stacks\Service;

interface StackCatalogInterface
{
    /**
     * @return array<string, string[]> Categoría => lista de servicios.
     */
    public function groupedServices(): array;

    /**
     * Resuelve un stack a partir de "categoria/servicio" o solo "servicio".
     */
    public function resolve(string $input): ?Service;

    /**
     * Resuelve la entrada del modo directo, incluyendo coincidencias por
     * nombre limpio (sin sufijo de dominio).
     */
    public function resolveForInput(string $input): ?Service;

    /**
     * Stacks cuyo nombre limpio coincide con la entrada.
     *
     * @return Service[]
     */
    public function candidates(string $input): array;

    /**
     * @return string[] Nombres de categorías ordenados.
     */
    public function categories(): array;

    /**
     * @return Service[] Servicios de una categoría, ordenados.
     */
    public function servicesOf(string $category): array;
}
