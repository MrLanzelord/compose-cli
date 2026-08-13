<?php

declare(strict_types=1);

namespace ComposeCli\Support;

/**
 * Integra el catálogo de stacks con la presentación: nombres limpios
 * e íconos. Se combina con el trait IconCatalog para un único punto
 * de verdad (DRY).
 */
trait UsesIconsAndNames
{
    use IconCatalog;
    use DisplayNames;
}
