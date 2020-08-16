<?php

declare(strict_types=1);

namespace Chiron\Views\Tests\Extension;

use Chiron\Container\Container;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Http\RequestContext;
use Chiron\Router\FastRoute\FastRouteRouter;
use Chiron\Router\Route;
use Chiron\Views\Extension\RoutingExtension;
use Chiron\Views\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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
