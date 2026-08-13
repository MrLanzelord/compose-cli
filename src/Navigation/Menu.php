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
            // Oculta el cursor mientras el menú está activo para evitar que se
            // vea el barrido del cursor durante el repintado.
            $this->output->write("\033[?25l");

            while (true) {
                // Redibujado en sitio y atómico: se construye el frame completo
                // y se emite en una sola escritura. Limpia la pantalla solo la
                // primera vez; después restaura la posición guardada y borra
                // desde ahí hacia abajo. Evita el parpadeo del clear por tecla.
                if ($first) {
                    $frame = "\033[2J\033[H\033[s";
                    $first = false;
                } else {
                    $frame = "\033[u\033[J";
                }

                $frame .= Palette::CYAN . Palette::BOLD . ' ' . $title . Palette::RESET . "\n";

                $navHint = $allowBack
                    ? '(↑/↓: Mover | Enter/→: Seleccionar | ←: Volver)'
                    : '(↑/↓: Mover | Enter: Seleccionar)';
                $frame .= Palette::DIM . ' ' . $navHint . Palette::RESET . "\n\n";

                foreach ($items as $i => $item) {
                    if ($i === $selected) {
                        $frame .= ' ' . Palette::GREEN . Palette::BOLD . '❯ ' . $item->label . Palette::RESET . "\n";
                    } else {
                        $frame .= '   ' . $item->label . "\n";
                    }
                }

                $this->output->write($frame);
                fflush(STDOUT);

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
            $this->output->write("\033[?25h");
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
