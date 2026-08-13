<?php

declare(strict_types=1);

namespace ComposeCli\Support;

use ComposeCli\Compose\StackStatus;

/**
 * Catálogo único de íconos Nerd Fonts y colores para categorías,
 * servicios y acciones. Fuente única para evitar duplicación (DRY).
 */
trait IconCatalog
{
    /**
     * @var array<string, string> Íconos por categoría.
     */
    private array $categoryIcons = [
        'ai' => "󰧑",        // Brain / IA
        'apps' => "󰀻",      // Aplicaciones
        'core' => "󰒋",      // Servidores / Infraestructura
        'instances' => "󰢹", // Máquinas virtuales
        'sites' => "󰖟",     // Sitios Web
    ];

    private string $defaultCategoryIcon = "󰉋";

    /**
     * @var array<string, string> Íconos por nombre de servicio limpio.
     */
    private array $serviceIcons = [
        // AI
        'ollama' => "󰚩",        // Bot / IA
        'open-webui' => "󰭹",    // Chat UI
        // APPS
        'immich' => "󰋩",        // Galería / Fotos
        'jellyfin' => "󰎁",      // Multimedia
        'nextcloud' => "󰅟",     // Nube
        // CORE
        'adguard' => "󰒃",       // Escudo DNS
        'crowdsec' => "󰞀",      // Seguridad / Firewall
        'duckdns' => "󰇧",       // Red / DNS
        'npm' => "󰛳",           // Proxy / Network
        'portainer' => "󰡨",     // Docker
        'vaultwarden' => "󰌋",   // Bóveda / Candado
        // INSTANCES
        'macos' => "󰀵",         // Apple / macOS
        // SITES
        'blog' => "󰖟",          // Sitio Web
    ];

    private string $defaultServiceIcon = "󰉌";

    /**
     * @var array<string, string> Íconos por estado del stack.
     */
    private array $statusIcons = [
        'up' => "",       // Círculo relleno: arriba
        'error' => "",    // Exclamación: con error
        'down' => "",     // Círculo vacío: abajo
        'unknown' => "",  // Interrogación: desconocido
    ];

    /**
     * @var array<string, string> Íconos por acción de Compose.
     */
    private array $actionIcons = [
        'back' => "󰁍",            // Flecha hacia atrás
        'up' => "󰐊",              // Play
        'up -d' => "󰜎",           // Play en segundo plano
        'up --build' => "󰒓",      // Martillo / reconstrucción
        'up -d --build' => "󰐊",   // Play blanco: inicio en segundo plano + rebuild
        'down' => "󰅙",            // Detener
        'stop' => "󰏤",            // Pausar
        'logs' => "󰈙",            // Registros
        'logs -f' => "󰐍",         // Registros en vivo
        'restart' => "󰑐",         // Reiniciar
    ];

    public function categoryIcon(string $category): string
    {
        $glyph = $this->categoryIcons[$category] ?? $this->defaultCategoryIcon;

        return match ($category) {
            'ai' => Palette::PURPLE,
            'apps' => Palette::YELLOW,
            'core' => Palette::CYAN,
            'instances' => Palette::BLUE,
            'sites' => Palette::GREEN,
            default => Palette::DIM,
        } . $glyph . Palette::RESET;
    }

    public function serviceIcon(string $displayName): string
    {
        $color = match (true) {
            in_array($displayName, ['ollama', 'open-webui', 'jellyfin'], true) => Palette::PURPLE,
            in_array($displayName, ['immich', 'vaultwarden'], true) => Palette::YELLOW,
            in_array($displayName, ['nextcloud', 'duckdns', 'npm'], true) => Palette::CYAN,
            in_array($displayName, ['adguard'], true) => Palette::GREEN,
            in_array($displayName, ['crowdsec'], true) => Palette::RED,
            in_array($displayName, ['portainer'], true) => Palette::BLUE,
            in_array($displayName, ['macos'], true) => Palette::WHITE,
            default => Palette::YELLOW,
        };

        $glyph = $this->serviceIcons[$displayName] ?? $this->defaultServiceIcon;

        return $color . $glyph . Palette::RESET;
    }

    public function statusIcon(StackStatus $status): string
    {
        $color = match ($status) {
            StackStatus::UP => Palette::GREEN,
            StackStatus::ERROR => Palette::RED,
            default => Palette::DIM,
        };

        $glyph = $this->statusIcons[$status->value] ?? $this->statusIcons['unknown'];

        return $color . $glyph . Palette::RESET;
    }

    public function actionIcon(string $action): string
    {
        $color = match (true) {
            in_array($action, ['back'], true) => Palette::YELLOW,
            in_array($action, ['up'], true) => Palette::GREEN,
            in_array($action, ['up -d'], true) => Palette::CYAN,
            in_array($action, ['up --build'], true) => Palette::YELLOW,
            in_array($action, ['up -d --build'], true) => Palette::WHITE,
            in_array($action, ['down', 'stop'], true) => Palette::RED,
            in_array($action, ['logs'], true) => Palette::BLUE,
            in_array($action, ['logs -f'], true) => Palette::PURPLE,
            in_array($action, ['restart'], true) => Palette::CYAN,
            default => Palette::DIM,
        };

        $glyph = $this->actionIcons[$action] ?? $this->defaultServiceIcon;

        return $color . $glyph . Palette::RESET;
    }
}
