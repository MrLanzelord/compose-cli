<?php

declare(strict_types=1);

namespace ComposeCli\Support;

/**
 * Limpia nombres de servicio eliminando sufijos de dominio solo para
 * la presentación en el menú. Reutilizado por Wizard y StackCatalog.
 */
trait DisplayNames
{
    /**
     * @param string[] $suffixes
     */
    public function displayName(string $serviceName, array $suffixes): string
    {
        usort($suffixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($suffixes as $suffix) {
            if (str_ends_with($serviceName, $suffix)) {
                return substr($serviceName, 0, -strlen($suffix));
            }
        }

        return $serviceName;
    }
}
