<?php

declare(strict_types=1);

namespace ComposeCli;

use ComposeCli\Compose\ActionCatalog;
use ComposeCli\Compose\ComposeRunner;
use ComposeCli\Navigation\Menu;
use ComposeCli\Navigation\Wizard;
use ComposeCli\Stacks\StackCatalog;
use ComposeCli\Stacks\Service;
use ComposeCli\Support\Config;
use ComposeCli\Support\EnvironmentGuard;
use ComposeCli\Support\Palette;
use ComposeCli\Support\Terminal;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * Kernel de la CLI: configura, parsea los argumentos y enruta hacia el
 * flujo interactivo (Wizard) o la ejecución directa.
 *
 * Los argumentos de las acciones de docker compose (ej: -d, --build) son
 * tokens con prefijo de opción, por lo que su extracción se hace de forma
 * manual y determinista; symfony/console se usa para la salida formateada.
 */
final class Application
{
    private const VERSION = '1.0.0';

    private const FLAGS = ['--version', '-V', '--help', '-h', '--dry-run'];

    /**
     * @param list<string> $argv
     */
    public function __construct(private readonly array $argv)
    {
    }

    public function run(): int
    {
        EnvironmentGuard::check();

        $output = new ConsoleOutput();
        $this->registerStyles($output);

        [$flags, $positional] = $this->parseTokens();

        if (isset($flags['--version']) || isset($flags['-V'])) {
            return $this->version($output);
        }

        if (isset($flags['--help']) || isset($flags['-h'])) {
            return $this->help($output);
        }

        $config = Config::fromEnvironment(dirname(__DIR__) . '/cli.php');
        $catalog = new StackCatalog($config->baseDir(), $config->domainSuffixes());
        $runner = new ComposeRunner();
        $dryRun = isset($flags['--dry-run']);

        if ($positional === []) {
            if ($dryRun) {
                return $this->help($output);
            }

            return $this->interactive($config, $catalog, $runner, $output);
        }

        return $this->direct($config, $catalog, $runner, $positional, $output, $dryRun);
    }

    /**
     * @return array{array<string, bool>, list<string>} [flags, posicionales]
     */
    private function parseTokens(): array
    {
        $flags = [];
        $positional = [];

        foreach (array_slice($this->argv, 1) as $token) {
            if (in_array($token, self::FLAGS, true)) {
                $flags[$token] = true;
            } else {
                $positional[] = $token;
            }
        }

        return [$flags, $positional];
    }

    private function interactive(
        Config $config,
        StackCatalog $catalog,
        ComposeRunner $runner,
        ConsoleOutputInterface $output,
    ): int {
        if ($catalog->groupedServices() === []) {
            $output->getErrorOutput()->writeln(
                Palette::RED
                . "❌ Error: No se encontraron archivos docker-compose.yml en {$config->baseDir()}"
                . Palette::RESET
            );

            return 1;
        }

        $menu = new Menu($output, new Terminal());
        $result = (new Wizard($catalog, $menu, new ActionCatalog()))->run();

        return $runner->run($result->service, $result->action);
    }

    /**
     * @param list<string> $positional
     */
    private function direct(
        Config $config,
        StackCatalog $catalog,
        ComposeRunner $runner,
        array $positional,
        ConsoleOutputInterface $output,
        bool $dryRun,
    ): int {
        $serviceInput = array_shift($positional);
        $action = array_shift($positional);
        $extra = $positional;

        $service = $catalog->resolveForInput($serviceInput);

        if ($service === null) {
            $candidates = $catalog->candidates($serviceInput);

            if ($candidates !== []) {
                $names = implode(', ', array_map(
                    static fn (Service $candidate): string => $candidate->key(),
                    $candidates,
                ));

                $output->getErrorOutput()->writeln(
                    Palette::RED
                    . "❌ Error: '$serviceInput' es ambiguo. Coincide con: $names. Usa el nombre completo."
                    . Palette::RESET
                );
            } else {
                $output->getErrorOutput()->writeln(
                    Palette::RED
                    . "❌ Error: '$serviceInput' no es un stack válido en {$config->baseDir()}"
                    . Palette::RESET
                );
            }

            return 1;
        }

        if ($action === null) {
            $output->getErrorOutput()->writeln(
                Palette::RED
                . '❌ Error: Debes especificar la acción (ej: up -d, down, logs)'
                . Palette::RESET
            );

            return 1;
        }

        return $runner->run($service, $action, $extra, $dryRun);
    }

    private function version(ConsoleOutputInterface $output): int
    {
        $output->writeln('compose-cli ' . self::VERSION);

        return 0;
    }

    private function help(ConsoleOutputInterface $output): int
    {
        $output->writeln('<h1>compose-cli</h1> — Gestión de stacks Docker Compose');
        $output->writeln('');
        $output->writeln('<h1>Uso:</h1>');
        $output->writeln('  cli.php                                  Menú interactivo');
        $output->writeln('  cli.php <stack> <acción> [extra...]      Ejecución directa');
        $output->writeln('  cli.php --help                           Muestra esta ayuda');
        $output->writeln('  cli.php --version                        Muestra la versión');
        $output->writeln('');
        $output->writeln('<h1>Stack:</h1>   categoria/servicio o solo servicio (ej: sites/foo o foo)');
        $output->writeln('<h1>Acción:</h1>  comando de docker compose (ej: up -d, down, logs)');
        $output->writeln('');
        $output->writeln('<h1>Opciones:</h1>');
        $output->writeln('  --dry-run      Imprime el comando sin ejecutarlo');
        $output->writeln('  -h, --help     Muestra esta ayuda');
        $output->writeln('  -V, --version  Muestra la versión');
        $output->writeln('');
        $output->writeln('<h1>Ejemplos:</h1>');
        $output->writeln('  cli.php sites/foo up -d');
        $output->writeln('  cli.php --dry-run foo up -d --build');

        return 0;
    }

    private function registerStyles(ConsoleOutput $output): void
    {
        $formatter = $output->getFormatter();
        $formatter->setStyle('h1', new OutputFormatterStyle('cyan', null, ['bold']));
    }
}
