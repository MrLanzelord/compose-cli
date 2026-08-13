<?php

declare(strict_types=1);

namespace ComposeCli\Navigation;

use ComposeCli\Compose\ActionCatalog;
use ComposeCli\Compose\StackStatusChecker;
use ComposeCli\Navigation\Contracts\MenuInterface;
use ComposeCli\Stacks\Contracts\StackCatalogInterface;
use ComposeCli\Stacks\Service;
use ComposeCli\Support\IconCatalog;

/**
 * Orquesta el flujo interactivo de selección en 3 pasos:
 * Categoría → Servicio → Acción.
 */
final class Wizard
{
    use IconCatalog;

    public function __construct(
        private readonly StackCatalogInterface $catalog,
        private readonly MenuInterface $menu,
        private readonly ActionCatalog $actions,
        private readonly StackStatusChecker $statusChecker = new StackStatusChecker(),
    ) {
    }

    public function run(): WizardResult
    {
        $step = 1;
        $category = null;
        $service = null;
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
                $selected = $this->selectService($category);

                if ($selected === null || $selected === MenuItem::BACK) {
                    $step = 1;

                    continue;
                }

                $resolved = $this->catalog->resolve($selected);

                if ($resolved === null) {
                    $step = 1;

                    continue;
                }

                $service = $resolved;
                $step = 3;
            } else {
                $selected = $this->selectAction($service);

                if ($selected === null || $selected === MenuItem::BACK) {
                    $step = 2;

                    continue;
                }

                $action = $selected;

                break;
            }
        }

        return new WizardResult($service, $action);
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

        return $this->menu->select($items, '📂 Paso 1/3: Selecciona una Categoría:');
    }

    private function selectService(string $category): ?string
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
            "📦 Paso 2/3: Selecciona un Servicio en '$category':",
            true,
        );
    }

    private function selectAction(Service $service): ?string
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

        return $this->menu->select(
            $items,
            "⚡ Paso 3/3: Selecciona la acción para '{$service->displayName}' "
            . $this->statusBadge($service) . ':',
            true,
        );
    }

    private function statusBadge(Service $service): string
    {
        $status = $this->statusChecker->status($service->composeFile);

        return $this->statusIcon($status) . ' ' . $status->label();
    }
}
