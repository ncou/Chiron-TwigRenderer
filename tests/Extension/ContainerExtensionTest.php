<?php

declare(strict_types=1);

namespace Chiron\Views\Tests\Extension;

use Chiron\Container\Container;
use Chiron\Views\Extension\ContainerExtension;
use Chiron\Views\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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
