<?php

declare(strict_types=1);

namespace Chiron\Views\Tests;

use Chiron\Views\TemplatePath;
use Chiron\Views\TwigRenderer;
use PHPUnit\Framework\TestCase;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigRendererTest extends TestCase
{
    /**
     * @var Twig_Environment
     */
    private $twigEnvironment;

    protected function setUp()
    {
        $this->twigEnvironment = new Environment(new FilesystemLoader());
    }

    public function assertTemplatePath($path, TemplatePath $templatePath, $message = null)
    {
        $message = $message ?: sprintf('Failed to assert TemplatePath contained path %s', $path);
        $this->assertEquals($path, $templatePath->getPath(), $message);
    }

    public function assertTemplatePathString($path, TemplatePath $templatePath, $message = null)
    {
        $message = $message ?: sprintf('Failed to assert TemplatePath casts to string path %s', $path);
        $this->assertEquals($path, (string) $templatePath, $message);
    }

    public function assertTemplatePathNamespace($namespace, TemplatePath $templatePath, $message = null)
    {
        $message = $message
            ?: sprintf('Failed to assert TemplatePath namespace matched %s', var_export($namespace, true));
        $this->assertEquals($namespace, $templatePath->getNamespace(), $message);
    }

    public function assertEmptyTemplatePathNamespace(TemplatePath $templatePath, $message = null)
    {
        $message = $message ?: 'Failed to assert TemplatePath namespace was empty';
        $this->assertEmpty($templatePath->getNamespace(), $message);
    }

    public function testConstructor()
    {
        $renderer = new TwigRenderer($this->twigEnvironment);
        $this->assertInstanceOf(TwigRenderer::class, $renderer);
        $this->assertEmpty($renderer->getPaths());
    }

    public function testCanAddPathWithEmptyNamespace()
    {
        $renderer = new TwigRenderer($this->twigEnvironment);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $paths = $renderer->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/Fixtures', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/Fixtures', $paths[0]);
        $this->assertEmptyTemplatePathNamespace($paths[0]);
    }

    public function testCanAddPathWithNamespace()
    {
        $renderer = new TwigRenderer($this->twigEnvironment);
        $renderer->addPath(__DIR__ . '/Fixtures', 'test');
        $paths = $renderer->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/Fixtures', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/Fixtures', $paths[0]);
        $this->assertTemplatePathNamespace('test', $paths[0]);
    }

    public function testDelegatesRenderingToUnderlyingImplementation()
    {
        $renderer = new TwigRenderer($this->twigEnvironment);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $result = $renderer->render('testTemplate.html.twig', ['hello' => 'Hi']);
        $this->assertEquals('Hi', $result);
    }

    public function testTemplateExistsWithExtensionInFileName()
    {
        $renderer = new TwigRenderer($this->twigEnvironment);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $result = $renderer->exists('testTemplate.html.twig');
        $this->assertTrue($result);
    }

    public function testTemplateExistsWithoutExtensionInFileName()
    {
        $renderer = new TwigRenderer($this->twigEnvironment);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $result = $renderer->exists('testTemplate');
        $this->assertTrue($result);
    }
}
