<?php

declare(strict_types=1);

namespace ComposeCli\Navigation;

use ComposeCli\Navigation\Contracts\MenuInterface;
use ComposeCli\Support\Palette;
use ComposeCli\Support\Terminal;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Menú interactivo de flechas implementado sobre flujos I/O estándar.
 *
 * Se integra con la salida de symfony/console para renderizar, pero la
 * captura de teclas se hace con stty + fread(STDIN) y no con los
 * componentes nativos de menú de symfony/console.
 */
final class Menu implements MenuInterface
{
    public function __construct(
        private readonly OutputInterface $output,
        private readonly Terminal $terminal,
    ) {
    }

    /**
     * @param MenuItem[] $items
     */
    public function select(array $items, string $title, bool $allowBack = false): ?string
    {
        if ($items === []) {
            return null;
        }

        if ($this->terminal->isInteractive()) {
            return $this->interactive($items, $title, $allowBack);
        }

        return $this->prompt($items, $title, $allowBack);
    }

    /**
     * @param MenuItem[] $items
     */
    private function interactive(array $items, string $title, bool $allowBack): ?string
    {
        $this->terminal->enableRawMode();
        $selected = 0;
        $first = true;

        try {
            while (true) {
                // Redibujado en sitio: limpiar la pantalla solo la primera vez
                // y, en cada tecla, restaurar la posición guardada y borrar solo
                // desde ahí hacia abajo. Evita el parpadeo del clear por tecla.
                if ($first) {
                    $this->output->write("\033[2J\033[H\033[s");
                    $first = false;
                } else {
                    $this->output->write("\033[u\033[J");
                }

                $this->output->writeln(Palette::CYAN . Palette::BOLD . ' ' . $title . Palette::RESET);

                $navHint = $allowBack
                    ? '(↑/↓: Mover | Enter/→: Seleccionar | ←: Volver)'
                    : '(↑/↓: Mover | Enter: Seleccionar)';
                $this->output->writeln(Palette::DIM . ' ' . $navHint . Palette::RESET);
                $this->output->writeln('');

                foreach ($items as $i => $item) {
                    if ($i === $selected) {
                        $this->output->writeln(
                            ' ' . Palette::GREEN . Palette::BOLD . '❯ ' . $item->label . Palette::RESET
                        );
                    } else {
                        $this->output->writeln('   ' . $item->label);
                    }
                }

                $key = $this->terminal->readKey();

                if ($key === Terminal::KEY_UP) {
                    $selected--;
                } elseif ($key === Terminal::KEY_DOWN) {
                    $selected++;
                } elseif ($key === Terminal::KEY_LEFT && $allowBack) {
                    return null;
                } elseif (
                    $key === Terminal::KEY_ENTER
                    || $key === Terminal::KEY_RETURN
                    || $key === Terminal::KEY_RIGHT
                ) {
                    return $items[$selected]->key;
                }

                if ($selected < 0) {
                    $selected = 0;
                }
                if ($selected >= count($items)) {
                    $selected = count($items) - 1;
                }
            }
        } finally {
            $this->terminal->restore();
        }
    }

    /**
     * Respaldo no-interactivo: prompt numérico sobre STDIN.
     *
     * @param MenuItem[] $items
     */
    private function prompt(array $items, string $title, bool $allowBack): ?string
    {
        while (true) {
            $this->output->writeln(Palette::CYAN . Palette::BOLD . ' ' . $title . Palette::RESET);

            foreach ($items as $i => $item) {
                $this->output->writeln(sprintf('   %d. %s', $i + 1, $item->label));
            }

            if ($allowBack) {
                $this->output->writeln('   0. .. (Volver)');
            }

            $this->output->write(Palette::GREEN . '❯ ' . Palette::RESET);

            $line = trim((string) fgets(STDIN));

            if ($line === '0') {
                if ($allowBack) {
                    return null;
                }

                continue;
            }

            if (is_numeric($line)) {
                $index = (int) $line - 1;

                if (isset($items[$index])) {
                    return $items[$index]->key;
                }
            }
        }
    }
}
