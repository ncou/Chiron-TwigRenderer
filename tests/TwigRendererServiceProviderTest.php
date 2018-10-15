<?php

use Chiron\Container\Container;
use Chiron\Views\Provider\TwigRendererServiceProvider;
use Chiron\Views\TemplateRendererInterface;
use Chiron\Views\TwigRenderer;
use PHPUnit\Framework\TestCase;

class TwigRendererServiceProviderTest extends TestCase
{
    public function testWithoutTemplatesSettingsInTheContainer()
    {
        $c = new Container();
        (new TwigRendererServiceProvider())->register($c);

        $renderer = $c->get(TwigRenderer::class);
        $this->assertInstanceOf(TwigRenderer::class, $renderer);
        $this->assertEmpty($renderer->getPaths());

        $this->assertEquals($renderer->getExtension(), 'html');

        // test the instance using the container alias
        $alias = $c->get(TemplateRendererInterface::class);
        $this->assertInstanceOf(TwigRenderer::class, $alias);
    }

    public function testWithTemplatesSettingsInTheContainer()
    {
        $c = new Container();
        $c['templates'] = ['extension' => 'html.twig', 'paths'     => ['foobar' => '/', 'tests/']];
        (new TwigRendererServiceProvider())->register($c);

        $renderer = $c->get(TwigRenderer::class);
        $this->assertInstanceOf(TwigRenderer::class, $renderer);
        $this->assertNotEmpty($renderer->getPaths());

        $this->assertEquals($renderer->getExtension(), 'html.twig');

        $paths = $renderer->getPaths();

        $this->assertEquals($paths[0]->getNamespace(), 'foobar');
        $this->assertEquals($paths[0]->getPath(), '');

        $this->assertNull($paths[1]->getNamespace());
        $this->assertEquals($paths[1]->getPath(), 'tests');
    }
}
