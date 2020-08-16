<?php

declare(strict_types=1);

namespace Chiron\Views\Command;

use Chiron\Console\AbstractCommand;
use Chiron\Filesystem\Filesystem;
use Chiron\Views\TemplateRendererInterface;
use Closure;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Twig\Loader\FilesystemLoader;

//https://github.com/symfony/twig-bridge/blob/master/Command/DebugCommand.php#L557
// ************* TESTS ***************
//https://github.com/symfony/twig-bridge/blob/master/Tests/Command/DebugCommandTest.php


final class TwigDebugCommand extends AbstractCommand
{
    /** @var \Twig\Environment */
    private $twig;

    protected static $defaultName = 'twig:debug';

    protected function configure(): void
    {
        $this->setDescription('Shows a list of twig functions, filters, globals, tests and the registered paths.');
    }

    public function perform(Filesystem $filesystem, TemplateRendererInterface $renderer): int
    {
        $this->twig = $renderer->twig();

        // TODO : à virer c'est un patch temporaire dans le code !!!!
        $decorated = false;

        $types = ['functions', 'filters', 'tests', 'globals'];
        foreach ($types as $index => $type) {
            $items = [];
            foreach ($this->twig->{'get' . ucfirst($type)}() as $name => $entity) {
                $items[$name] = $name . $this->getPrettyMetadata($type, $entity, $decorated);
            }

            if (! $items) {
                continue;
            }

            $this->newline();
            $this->notice(ucfirst($type));
            ksort($items);
            $this->listing($items);
        }

        // TODO : gérer le cas ou le tableau de $paths est vide et dans ce cas afficher le message : 'No template paths configured for your application.'
        $paths = $this->getLoaderPaths();

        $this->newline();
        $this->notice('Loader Paths');
        //$this->newline();
        $table = $this->table(['Namespace', 'Path(s)'], $this->buildTableRows($paths));

        $table->render();

        return self::SUCCESS;
    }

    private function getLoaderPaths(): array
    {
        $loader = $this->twig->getLoader();

        $loaderPaths = [];
        foreach ($loader->getNamespaces() as $namespace) {
            //$paths = array_map([$this, 'getRelativePath'], $loader->getPaths($namespace));
            $paths = $loader->getPaths($namespace);

            if ($namespace === FilesystemLoader::MAIN_NAMESPACE) {
                $namespace = '(None)';
            } else {
                $namespace = '@' . $namespace;
            }

            $loaderPaths[$namespace] = array_merge($loaderPaths[$namespace] ?? [], $paths);
        }

        return $loaderPaths;
    }

    private function buildTableRows(array $loaderPaths): array
    {
        $rows = [];
        $firstNamespace = true;
        $prevHasSeparator = false;

        foreach ($loaderPaths as $namespace => $paths) {
            if (! $firstNamespace && ! $prevHasSeparator && count($paths) > 1) {
                $rows[] = ['', ''];
            }
            $firstNamespace = false;
            foreach ($paths as $path) {
                $rows[] = [$namespace, $path . DIRECTORY_SEPARATOR];
                $namespace = '';
            }
            if (count($paths) > 1) {
                $rows[] = ['', ''];
                $prevHasSeparator = true;
            } else {
                $prevHasSeparator = false;
            }
        }
        if ($prevHasSeparator) {
            array_pop($rows);
        }

        return $rows;
    }

    private function getPrettyMetadata(string $type, $entity, bool $decorated): ?string
    {
        if ($type === 'tests') {
            return '';
        }

        try {
            $meta = $this->getMetadata($type, $entity);
            if ($meta === null) {
                return '(unknown?)';
            }
        } catch (\UnexpectedValueException $e) {
            return sprintf(' <error>%s</error>', $decorated ? OutputFormatter::escape($e->getMessage()) : $e->getMessage());
        }

        if ($type === 'globals') {
            if (is_object($meta)) {
                return ' = object(' . get_class($meta) . ')';
            }

            $description = substr(@json_encode($meta), 0, 50);

            return sprintf(' = %s', $decorated ? OutputFormatter::escape($description) : $description);
        }

        if ($type === 'functions') {
            return '(' . implode(', ', $meta) . ')';
        }

        if ($type === 'filters') {
            return $meta ? '(' . implode(', ', $meta) . ')' : '';
        }

        return null;
    }

    private function getMetadata(string $type, $entity)
    {
        if ($type === 'globals') {
            return $entity;
        }
        if ($type === 'tests') {
            return null;
        }
        if ($type === 'functions' || $type === 'filters') {
            $cb = $entity->getCallable();
            if ($cb === null) {
                return null;
            }
            if (is_array($cb)) {
                if (! method_exists($cb[0], $cb[1])) {
                    return null;
                }
                $refl = new ReflectionMethod($cb[0], $cb[1]);
            } elseif (is_object($cb) && ! $cb instanceof Closure) {
                $refl = new ReflectionMethod($cb, '__invoke');
            } elseif (is_object($cb) && $cb instanceof Closure) {
                $refl = new ReflectionFunction($cb);
            } elseif (function_exists($cb)) {
                $refl = new ReflectionFunction($cb);
            } elseif (is_string($cb) && preg_match('{^(.+)::(.+)$}', $cb, $m) && method_exists($m[1], $m[2])) {
                $refl = new ReflectionMethod($m[1], $m[2]);
            } else {
                throw new \UnexpectedValueException('Unsupported callback type.');
            }

            $args = $refl->getParameters();

            //dd($args);

            // filter out context/environment args
            if ($entity->needsEnvironment()) {
                array_shift($args);
            }
            if ($entity->needsContext()) {
                array_shift($args);
            }

            if ($type === 'filters') {
                // remove the value the filter is applied on
                array_shift($args);
            }

            // format args
            $args = array_map(function (ReflectionParameter $param) {
                if ($param->isDefaultValueAvailable()) {
                    return $param->getName() . ' = ' . json_encode($param->getDefaultValue());
                }

                return $param->getName();
            }, $args);

            return $args;
        }

        return null;
    }
}
