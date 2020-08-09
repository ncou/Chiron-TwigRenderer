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

use Chiron\Views\Extension\RoutingExtension;

use Chiron\Router\FastRoute\FastRouteRouter;
use Chiron\Router\Route;

use Psr\Http\Message\ServerRequestInterface;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Http\RequestContext;

class RoutingExtensionTest extends TestCase
{
    /**
     * @var Twig_Environment
     */
    private $twigEnvironment;

    protected function setUp()
    {
        $this->twigEnvironment = new Environment(new FilesystemLoader());

        $router = new FastRouteRouter();
        $router->addRoute(Route::any('/my/target/path/')->name('route_name'));

        $container = new Container();
        $request = new ServerRequest('GET', new Uri('https://www.foo.bar/'));
        $container->bind(ServerRequestInterface::class, $request);
        $context = new RequestContext($container);

        $this->twigEnvironment->addExtension(new RoutingExtension($router, $context));
    }

    public function testRoutingExtensionAbsoluteUrlForMethod()
    {
        $renderer = new TwigRenderer($this->twigEnvironment);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $result = $renderer->render('absoluteUrlFor.html.twig');

        $this->assertEquals('https://www.foo.bar/my/target/path/', $result);
    }

    public function testRoutingExtensionRelativeUrlForMethod()
    {
        $renderer = new TwigRenderer($this->twigEnvironment);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $result = $renderer->render('relativeUrlFor.html.twig');

        $this->assertEquals('/my/target/path/', $result);
    }
}
