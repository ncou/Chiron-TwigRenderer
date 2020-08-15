<?php

declare(strict_types=1);

namespace Chiron\Views\Tests;

use Chiron\Container\Container;
use Chiron\Views\Provider\TwigRendererServiceProvider;
use Chiron\Views\TemplateRendererInterface;
use Chiron\Views\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

use Chiron\Boot\Configure;
use Chiron\Boot\Directories;

use Chiron\Views\TwigEngineFactory;
use Chiron\Views\Config\TwigConfig;

class TwigRendererServiceProviderTest extends TestCase
{
    public function testWithoutTemplatesSettingsInTheContainer()
    {
        $c = $this->initContainer();
        // register the provider.
        (new TwigRendererServiceProvider())->register($c);

        $renderer = $c->get(TwigRenderer::class);
        $this->assertInstanceOf(TwigRenderer::class, $renderer);
        $this->assertInstanceOf(Environment::class, $renderer->twig());

        // test the instance using the container alias
        $alias = $c->get(TemplateRendererInterface::class);
        $this->assertInstanceOf(TwigRenderer::class, $alias);
        $this->assertInstanceOf(Environment::class, $alias->twig());

        // test the default cache setting.
        $twig = $alias->twig();
        $this->assertEquals($twig->getCache(), strtr(sys_get_temp_dir(), '\\', '/') . '/twig/');
    }

    public function testWithTemplatesSettingsInTheContainer()
    {
        $c = $this->initContainer();
        // register the provider.
        (new TwigRendererServiceProvider())->register($c);

        // override the twig settings.
        $factory = new TwigEngineFactory();
        $environment = $factory(new TwigConfig(['options' => ['cache' => false]]));

        $c->singleton(Environment::class, $environment);

        $renderer = $c->get(TwigRenderer::class);
        $this->assertInstanceOf(TwigRenderer::class, $renderer);
        $this->assertInstanceOf(Environment::class, $renderer->twig());

        // test the default cache setting.
        $this->assertEquals($renderer->twig()->getCache(), false);

        // test the instance using the container alias
        $alias = $c->get(TemplateRendererInterface::class);
        $this->assertInstanceOf(TwigRenderer::class, $alias);
        $this->assertInstanceOf(Environment::class, $alias->twig());

        // test the default cache setting.
        $this->assertEquals($alias->twig()->getCache(), false);
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
