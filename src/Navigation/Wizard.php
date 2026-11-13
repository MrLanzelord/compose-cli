<?php

declare(strict_types=1);

namespace ComposeCli\Navigation;

use ComposeCli\Compose\ActionCatalog;
use ComposeCli\Compose\ServiceResolver;
use ComposeCli\Compose\StackStatusChecker;
use ComposeCli\Navigation\Contracts\MenuInterface;
use ComposeCli\Stacks\Contracts\StackCatalogInterface;
use ComposeCli\Stacks\Service;
use ComposeCli\Support\IconCatalog;

/**
 * Orquesta el flujo interactivo de selección en 4 pasos:
 * Categoría → Stack (cliente) → Servicio (o todos) → Acción.
 */
final class Wizard
{
    use IconCatalog;

    private const ALL_SERVICES = '__ALL__';

    public function __construct(
        private readonly StackCatalogInterface $catalog,
        private readonly MenuInterface $menu,
        private readonly ActionCatalog $actions,
        private readonly ServiceResolver $serviceResolver,
        private readonly StackStatusChecker $statusChecker = new StackStatusChecker(),
    ) {
    }

    public function run(): WizardResult
    {
        $step = 1;
        $category = null;
        $stack = null;
        $target = null;
        $action = null;

        while (true) {
            if ($step === 1) {
                $selected = $this->selectCategory();

                if ($selected === null) {
                    continue;
                }

                $category = $selected;
                $step = 2;
            } elseif ($step === 2) {
                $selected = $this->selectStack($category);

                if ($selected === null || $selected === MenuItem::BACK) {
                    $step = 1;

                    continue;
                }

                $resolved = $this->catalog->resolve($selected);

                if ($resolved === null) {
                    $step = 1;

                    continue;
                }

                $stack = $resolved;
                $step = 3;
            } elseif ($step === 3) {
                $selected = $this->selectComposeService($stack);

                if ($selected === null || $selected === MenuItem::BACK) {
                    $step = 2;

                    continue;
                }

                $target = $selected === self::ALL_SERVICES ? null : $selected;
                $step = 4;
            } else {
                $selected = $this->selectAction($stack, $target);

                if ($selected === null || $selected === MenuItem::BACK) {
                    $step = 3;

                    continue;
                }

                $action = $selected;

                break;
            }
        }

        return new WizardResult($stack, $action, $target);
    }

    private function selectCategory(): ?string
    {
        $items = [];

        foreach ($this->catalog->categories() as $category) {
            $items[] = new MenuItem(
                $category,
                $this->categoryIcon($category) . '  ' . $category,
            );
        }

        return $this->menu->select($items, '📂 Paso 1/4: Selecciona una Categoría:');
    }

    private function selectStack(string $category): ?string
    {
        $items = [
            new MenuItem(
                MenuItem::BACK,
                $this->actionIcon('back') . '  .. (Volver a Categorías)',
            ),
        ];

        foreach ($this->catalog->servicesOf($category) as $service) {
            $items[] = new MenuItem(
                $service->key(),
                $this->serviceIcon($service->displayName)
                . '  '
                . $service->displayName
                . '  '
                . $this->statusBadge($service),
            );
        }

        return $this->menu->select(
            $items,
            "📦 Paso 2/4: Selecciona un Stack en '$category':",
            true,
        );
    }

    private function selectComposeService(Service $stack): ?string
    {
        $items = [
            new MenuItem(
                MenuItem::BACK,
                $this->actionIcon('back') . '  .. (Volver a Stacks)',
            ),
            new MenuItem(
                self::ALL_SERVICES,
                $this->actionIcon('up') . '  Todos los servicios',
            ),
        ];

        foreach ($this->serviceResolver->services($stack->composeFile) as $serviceName) {
            $items[] = new MenuItem(
                $serviceName,
                $this->serviceIcon($serviceName) . '  ' . $serviceName,
            );
        }

        return $this->menu->select(
            $items,
            "🐳 Paso 3/4: Servicio en '{$stack->displayName}':",
            true,
        );
    }

    private function selectAction(Service $stack, ?string $target): ?string
    {
        $items = [
            new MenuItem(
                MenuItem::BACK,
                $this->actionIcon('back') . '  .. (Volver a Servicios)',
            ),
        ];

        foreach ($this->actions->labels() as $label) {
            $items[] = new MenuItem(
                $label,
                $this->actionIcon($label)
                . '  '
                . $label
                . ' ('
                . $this->actions->description($label)
                . ')',
            );
        }

        $subject = $target ?? $stack->displayName;

        return $this->menu->select(
            $items,
            "⚡ Paso 4/4: Acción para '{$subject}' " . $this->statusBadge($stack) . ':',
            true,
        );
    }

    private function statusBadge(Service $service): string
    {
        $status = $this->statusChecker->status($service->composeFile);

        return $this->statusIcon($status) . ' ' . $status->label();
    }
}
