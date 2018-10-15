<?php

use Chiron\Views\TemplatePath;
use Chiron\Container\Container;
use Chiron\Views\TwigRenderer;
use Chiron\Views\Provider\TwigRendererServiceProvider;
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
        // TODO : ajouter un assert sur l'extension du template qui doit avoir la valeur par défaut = html
    }

    public function testWithTemplatesSettingsInTheContainer()
    {
        $c = new Container();
        $c->set('templates', ['extension' => 'html.twig', 'paths'     => ['foobar' => '/', 'tests/']]);
        (new TwigRendererServiceProvider())->register($c);

        $renderer = $c->get(TwigRenderer::class);

        $this->assertInstanceOf(TwigRenderer::class, $renderer);

        $paths = $renderer->getPaths();

        $this->assertEquals($paths[0]->getNamespace(), 'foobar');
        $this->assertEquals($paths[0]->getPath(), '');

        $this->assertNull($paths[1]->getNamespace());
        $this->assertEquals($paths[1]->getPath(), 'tests');

        // TODO : ajouter un assert sur l'extension du template qui doit avoir la valeur par défaut = html
    }
}
