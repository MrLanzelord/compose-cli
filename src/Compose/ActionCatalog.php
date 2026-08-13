<?php

declare(strict_types=1);

namespace ComposeCli\Compose;

/**
 * Catálogo de acciones del menú: etiquetas, íconos y orden.
 * Fuente única para el paso de selección de acción (DRY).
 */
final class ActionCatalog
{
    /** @var string[] */
    private const ORDER = [
        'up',
        'up -d',
        'up --build',
        'up -d --build',
        'down',
        'stop',
        'logs',
        'logs -f',
        'restart',
    ];

    /** @var string[] */
    private const DESCRIPTIONS = [
        'up' => 'Iniciar',
        'up -d' => 'Iniciar en segundo plano',
        'up --build' => 'Reconstruir e iniciar',
        'up -d --build' => 'Reconstruir e iniciar en segundo plano',
        'down' => 'Detener y eliminar contenedores',
        'stop' => 'Pausar/Detener',
        'logs' => 'Ver registros',
        'logs -f' => 'Seguir registros en vivo',
        'restart' => 'Reiniciar',
    ];

    /**
     * @return string[] Etiquetas de acciones en orden de presentación.
     */
    public function labels(): array
    {
        return self::ORDER;
    }

    public function description(string $label): string
    {
        return self::DESCRIPTIONS[$label] ?? '';
    }

    public function toAction(string $label): ?ComposeAction
    {
        return ComposeAction::tryFrom($label);
    }
}
