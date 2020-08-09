<?php

declare(strict_types=1);

namespace Chiron\Views\Tests\Extension;

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

use Chiron\Views\Extension\ContainerExtension;

class ContainerExtensionTest extends TestCase
{
    /**
     * @var Twig_Environment
     */
    private $twigEnvironment;

    protected function setUp()
    {
        $this->twigEnvironment = new Environment(new FilesystemLoader());

        $container = new Container();
        $this->twigEnvironment->addExtension(new ContainerExtension($container));
    }

    public function testContainerExtensionGetMethod()
    {
        $renderer = new TwigRenderer($this->twigEnvironment);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $result = $renderer->render('get.html.twig');

        $this->assertEquals('Hello world', $result);
    }
}
