<?php

declare(strict_types=1);

namespace Chiron\Views\Command\Tests;

use Chiron\Views\TemplatePath;
use Chiron\Views\TwigRenderer;
use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

use Chiron\Views\Command\TwigCompileCommand;

use Chiron\Console\CommandLoader\CommandLoader;
use Chiron\Container\Container;
use Chiron\Console\Console;

use Chiron\Boot\Configure;
use Chiron\Boot\Directories;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use Chiron\Views\TemplateRendererInterface;
use Chiron\Views\TwigRendererFactory;
use Chiron\Views\Config\TwigConfig;

class TwigCompileCommandTest extends TestCase
{
    public function testCompileCorrectFile()
    {
        $tester = $this->createCommandTester(__DIR__.'/Fixtures/correct/');
        //$ret = $tester->execute(['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);
        $ret = $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);

        $this->assertEquals(0, $ret, 'Returns 0 in case of success');
        $this->assertStringContainsString('All 1 Twig files contain valid syntax.', trim($tester->getDisplay()));
        $this->assertStringContainsString('OK in', trim($tester->getDisplay()));
    }

    public function testCompileIncorrectFile()
    {
        $tester = $this->createCommandTester(__DIR__.'/Fixtures/incorrect/');
        $ret = $tester->execute([], ['decorated' => false]);

        $this->assertEquals(1, $ret, 'Returns 1 in case of error');
        $this->assertStringContainsString('0 Twig files have valid syntax and 1 contain errors.', trim($tester->getDisplay()));
    }

    public function testCompileFileCompileTimeException()
    {
        $tester = $this->createCommandTester(__DIR__.'/Fixtures/exception/');
        $ret = $tester->execute([], ['decorated' => false]);

        $this->assertEquals(1, $ret, 'Returns 1 in case of error');
        $this->assertStringContainsString('0 Twig files have valid syntax and 1 contain errors.', trim($tester->getDisplay()));
    }

    public function testCompileExceptionForEmptyFolder()
    {
        $tester = $this->createCommandTester(__DIR__.'/Fixtures/empty/');

        $ret = $tester->execute([], ['decorated' => false]);

        $this->assertEquals(0, $ret, 'Returns 0 in case of success');
        $this->assertStringContainsString('No twig files found in the loader paths.', trim($tester->getDisplay()));
    }

    public function testCompileIncorrectFileForCustomLexer()
    {
        $tester = $this->createCommandTester(__DIR__.'/Fixtures/lexer/');
        $ret = $tester->execute([], ['decorated' => false]);

        $this->assertEquals(1, $ret, 'Returns 1 in case of error');
        $this->assertStringContainsString('0 Twig files have valid syntax and 1 contain errors.', trim($tester->getDisplay()));
    }

    public function testCompileCorrectFileForCustomLexer()
    {
        $tester = $this->createCommandTester(__DIR__.'/Fixtures/lexer/', null, ['tag_block' => ['{%', '!}']]);
        $ret = $tester->execute([]);

        $this->assertEquals(0, $ret, 'Returns 0 in case of success');
        $this->assertStringContainsString('All 1 Twig files contain valid syntax.', trim($tester->getDisplay()));
    }

    public function testCompileCorrectFileWithNamespace()
    {
        $tester = $this->createCommandTester(__DIR__.'/Fixtures/namespaced/', 'namespaced');
        $ret = $tester->execute([]);

        $this->assertEquals(0, $ret, 'Returns 0 in case of success');
        $this->assertStringContainsString('All 1 Twig files contain valid syntax.', trim($tester->getDisplay()));
    }

    public function testCompileEmptyFileWithNamespace()
    {
        $tester = $this->createCommandTester(__DIR__.'/Fixtures/namespaced_empty/', 'namespaced_empty');
        $ret = $tester->execute([]);

        $this->assertEquals(0, $ret, 'Returns 0 in case of success');
        $this->assertStringContainsString('No twig files found in the loader paths.', trim($tester->getDisplay()));
    }

    private function createCommandTester(string $path, ?string $namespace = null, array $lexer = []): CommandTester
    {
        $container = $this->initContainer();

        $factory = new TwigRendererFactory();
        $renderer = $factory(new TwigConfig(['lexer' => $lexer]));
        $renderer->addPath($path, $namespace);

        $container->singleton(TemplateRendererInterface::class, $renderer);

        $commandLoader = new CommandLoader($container);

        $console = new Console($commandLoader);

        $console->addCommand('twig:compile', TwigCompileCommand::class);
        $command = $console->find('twig:compile');

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
}
