<?php

declare(strict_types=1);

namespace Chiron\Views\Tests\Command;

use Chiron\Views\TemplatePath;
use Chiron\Views\TwigRenderer;
use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

use Chiron\Views\Command\TwigClearCommand;

use Chiron\Console\CommandLoader\CommandLoader;
use Chiron\Container\Container;
use Chiron\Console\Console;

use Chiron\Boot\Configure;
use Chiron\Boot\Directories;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use Chiron\Filesystem\Filesystem;

use Chiron\Views\TemplateRendererInterface;
use Chiron\Views\TwigRendererFactory;
use Chiron\Views\Config\TwigConfig;
use Twig\Cache\NullCache;

class TwigClearCommandTest extends TestCase
{
    public function testClearCacheSuccess()
    {
        $cacheDir = self::getUniqueTmpDirectory();
        $tester = $this->createCommandTester($cacheDir);
        //$ret = $tester->execute(['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);
        $ret = $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);

        $this->assertEquals(0, $ret, 'Returns 0 in case of success');
        $this->assertStringContainsString('Twig cache cleaned.', trim($tester->getDisplay()));
    }

    public function testClearCacheFailCauseCacheIsABoolean()
    {
        $cacheDir = false;
        $tester = $this->createCommandTester($cacheDir);
        //$ret = $tester->execute(['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);
        $ret = $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);

        $this->assertEquals(1, $ret, 'Returns 1 in case of error');
        $this->assertStringContainsString('Twig cache option is not defined as an absolute path, so it can\'t be cleaned.', trim($tester->getDisplay()));
    }

    public function testClearCacheFailCauseCacheIsACacheInterface()
    {
        $cacheDir = new NullCache();
        $tester = $this->createCommandTester($cacheDir);
        //$ret = $tester->execute(['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);
        $ret = $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);

        $this->assertEquals(1, $ret, 'Returns 1 in case of error');
        $this->assertStringContainsString('Twig cache option is not defined as an absolute path, so it can\'t be cleaned.', trim($tester->getDisplay()));
    }


    public function testClearCacheFailCauseDirectoryCacheDoesntExist()
    {
        $cacheDir = 'non_existing_directory';
        $tester = $this->createCommandTester($cacheDir);
        //$ret = $tester->execute(['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);
        $ret = $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);

        $this->assertEquals(1, $ret, 'Returns 1 in case of error');
        $this->assertStringContainsString('Twig cache failed to be cleaned.', trim($tester->getDisplay()));
    }

    // $cache mixed Could have the value : false / string / \Twig\Cache\CacheInterface::class
    private function createCommandTester($cache): CommandTester
    {
        $container = $this->initContainer();

        $factory = new TwigRendererFactory();
        $config = new TwigConfig(['options' => ['cache' => $cache]]);
        $renderer = $factory($config);

        $container->singleton(TemplateRendererInterface::class, $renderer);

        $commandLoader = new CommandLoader($container);

        $console = new Console($commandLoader);

        $console->addCommand('twig:clear', TwigClearCommand::class);
        $command = $console->find('twig:clear');

        return new CommandTester($command);
    }

    private function initContainer(): Container
    {
        $container = new Container();
        $container->setAsGlobal();

        // TODO : il faudra surement initialiser la matuation sur les classes de config plutot que de faire un merge !!!!
        $configure = $container->get(Configure::class);
        $configure->merge('settings', ['debug' => true, 'charset' => 'UTF-8', 'timezone' => 'UTC']);


        $directories = $container->get(Directories::class);
        $directories->set('@cache', sys_get_temp_dir());

        return $container;
    }

    private static function getUniqueTmpDirectory()
    {
        $attempts = 5;
        $root = sys_get_temp_dir();

        do {
            $unique = $root . DIRECTORY_SEPARATOR . uniqid('composer-test-' . rand(1000, 9000));

            if (!file_exists($unique) && mkdir($unique, 0777)) {
                return realpath($unique);
            }
        } while (--$attempts);

        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }
}
