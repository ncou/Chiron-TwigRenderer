<?php

declare(strict_types=1);

namespace Chiron\Views\Tests;

use Chiron\Container\Container;
use Chiron\Views\Tests\Fixtures\CustomExtension;
use Chiron\Views\Tests\Fixtures\StaticAndConsts;
use Chiron\Views\TwigRenderer;
use Chiron\Views\TwigRendererFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TwigRendererFactoryTest extends TestCase
{
    private function createRenderer(array $config = [], ContainerInterface $c = null): TwigRenderer
    {
        if ($c === null) {
            $c = new Container();
        }

        $c->set('config', $config);

        $renderer = call_user_func(new TwigRendererFactory(), $c);

        $renderer->setExtension('twig');
        $renderer->addPath(__DIR__ . '/Fixtures');

        return $renderer;
    }

    public function testConstructor()
    {
        $renderer = $this->createRenderer();
        $this->assertInstanceOf(TwigRenderer::class, $renderer);

        $twig = $renderer->twig();
        $this->assertInstanceOf(\Twig_Environment::class, $twig);
    }

    public function testStaticAndConsts()
    {
        $config['twig']['globals']['staticClass'] = ['class' => StaticAndConsts::class];

        $renderer = $this->createRenderer($config);

        $content = $renderer->render('staticAndConsts.twig');
        $this->assertContains('I am a const!', $content);
        $this->assertContains('I am a static var!', $content);
        $this->assertContains('I am a static function with param pam-param!', $content);
    }

    public function testDebug()
    {
        $config['twig']['options']['debug'] = true;

        $renderer = $this->createRenderer($config);

        $content = $renderer->render('debug.twig', ['foo' => 'bar']);
        $this->assertContains('string(3) "bar"', $content);
    }

    public function testTimezoneDefined()
    {
        $config['twig']['date']['timezone'] = 'Europe/Paris';

        date_default_timezone_set('UTC');
        $renderer = $this->createRenderer($config);

        $twig = $renderer->twig();
        $timezone = $twig->getExtension(\Twig_Extension_Core::class)->getTimezone();

        $this->assertEquals('Europe/Paris', $timezone->getName());
    }

    public function testTimezoneNotDefined()
    {
        $config['twig']['date']['timezone'] = null;

        date_default_timezone_set('UTC');
        $renderer = $this->createRenderer($config);

        $twig = $renderer->twig();
        $timezone = $twig->getExtension(\Twig_Extension_Core::class)->getTimezone();

        $this->assertEquals('UTC', $timezone->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTimezoneInvalidFormat()
    {
        $config['twig']['date']['timezone'] = 'Foobar';

        $renderer = $this->createRenderer($config);
    }

    public function testDateFormat()
    {
        $config['twig']['date']['format'] = 'Foo';
        $config['twig']['date']['interval_format'] = 'Bar';

        $renderer = $this->createRenderer($config);

        $twig = $renderer->twig();
        $dateFormats = $twig->getExtension(\Twig_Extension_Core::class)->getDateFormat();

        $this->assertEquals('Foo', $dateFormats[0]);
        $this->assertEquals('Bar', $dateFormats[1]);
    }

    public function testNumberFormat()
    {
        $config['twig']['number_format']['decimals'] = 10;
        $config['twig']['number_format']['decimal_point'] = 'Foo';
        $config['twig']['number_format']['thousands_separator'] = 'Bar';

        $renderer = $this->createRenderer($config);

        $twig = $renderer->twig();
        $numberFormats = $twig->getExtension(\Twig_Extension_Core::class)->getNumberFormat();

        $this->assertEquals(10, $numberFormats[0]);
        $this->assertEquals('Foo', $numberFormats[1]);
        $this->assertEquals('Bar', $numberFormats[2]);
    }

    public function testSimpleFunctions()
    {
        $config['twig']['functions'] = [
            'json_encode' => '\Chiron\Views\Tests\Fixtures\JsonHelper::encode',
            new \Twig_SimpleFunction('rot13', 'str_rot13'),
            new \Twig_SimpleFunction('add_*', function ($symbols, $val) {
                return $val . $symbols;
            }, ['is_safe' => ['html']]),
            'callable_rot13' => function ($string) {
                return str_rot13($string);
            },
            'callable_add_*' => function ($symbols, $val) {
                return $val . $symbols;
            },
            'callable_sum' => [function ($a, $b) {
                return $a + $b;
            }, ['is_safe' => ['html']]],
        ];

        $renderer = $this->createRenderer($config);

        $content = $renderer->render('simpleFunctions1.twig');
        $this->assertEquals($content, 'Sbbone');
        $content = $renderer->render('simpleFunctions2.twig');
        $this->assertEquals($content, 'val43');
        $content = $renderer->render('simpleFunctions3.twig');
        $this->assertEquals($content, 'Sbbone');
        $content = $renderer->render('simpleFunctions4.twig');
        $this->assertEquals($content, 'val43');
        $content = $renderer->render('simpleFunctions5.twig');
        $this->assertEquals($content, '6');
        $content = $renderer->render('simpleFunctions6.twig');
        $this->assertContains('echo', $content);
        $this->assertContains('variable', $content);
    }

    public function testSimpleFilters()
    {
        $config['twig']['filters'] = [
            'string_rot13' => 'str_rot13',
            new \Twig_SimpleFilter('rot13', 'str_rot13'),
            new \Twig_SimpleFilter('add_*', function ($symbols, $val) {
                return $val . $symbols;
            }, ['is_safe' => ['html']]),
            'callable_rot13' => function ($string) {
                return str_rot13($string);
            },
            'callable_add_*' => [function ($symbols, $val) {
                return $val . $symbols;
            }, ['is_safe' => ['html']]],
        ];

        $renderer = $this->createRenderer($config);

        $content = $renderer->render('simpleFilters1.twig');
        $this->assertEquals($content, 'Sbbone');
        $content = $renderer->render('simpleFilters2.twig');
        $this->assertEquals($content, 'val42');
        $content = $renderer->render('simpleFilters3.twig');
        $this->assertEquals($content, 'Sbbone');
        $content = $renderer->render('simpleFilters4.twig');
        $this->assertEquals($content, 'val42');
        $content = $renderer->render('simpleFilters5.twig');
        $this->assertEquals($content, 'Sbbone');
    }

    public function testExtension()
    {
        $config['twig']['extensions'] = [
            new CustomExtension(),
        ];

        $renderer = $this->createRenderer($config);

        $content = $renderer->render('extension.twig');
        $this->assertEquals($content, 'Sbbone');
    }

    public function testExtensionDefinedInContainer()
    {
        $c = new Container();
        $c->set(CustomExtension::class, new CustomExtension());

        $config['twig']['extensions'] = [
            CustomExtension::class,
        ];

        $renderer = $this->createRenderer($config, $c);

        $content = $renderer->render('extension.twig');
        $this->assertEquals($content, 'Sbbone');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Twig extension must be an instance of Twig_ExtensionInterface; "string" given.
     */
    public function testExtensionNotDefinedInContainer()
    {
        $config['twig']['extensions'] = [
            CustomExtension::class,
        ];

        $renderer = $this->createRenderer($config);
    }

    public function testRuntimeLoader()
    {
        $c = new Container();
        $config['twig']['runtime_loaders'] = [
            new \Twig_ContainerRuntimeLoader($c),
        ];

        $renderer = $this->createRenderer($config);

        $this->assertInstanceOf(TwigRenderer::class, $renderer);
    }

    public function testRuntimeLoaderDefinedInContainer()
    {
        $c = new Container();
        $c->set('RuntimeLoader', new \Twig_ContainerRuntimeLoader($c));

        $config['twig']['runtime_loaders'] = [
            'RuntimeLoader',
        ];

        $renderer = $this->createRenderer($config, $c);

        $this->assertInstanceOf(TwigRenderer::class, $renderer);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Twig runtime loader must be an instance of Twig_RuntimeLoaderInterface; "string" given.
     */
    public function testRuntimeLoaderNotDefinedInContainer()
    {
        $config['twig']['runtime_loaders'] = [
            'RuntimeLoader',
        ];

        $renderer = $this->createRenderer($config);
    }

    public function testLexerOptions()
    {
        $config['twig']['lexer'] = [
            'tag_comment' => ['{*', '*}'],
        ];

        $renderer = $this->createRenderer($config);

        $content = $renderer->render('lexer.twig');

        $this->assertFalse(strpos($content, 'CUSTOM_LEXER_TWIG_COMMENT'), 'Custom comment lexerOptions were not applied: ' . $content);
        $this->assertTrue(strpos($content, 'DEFAULT_TWIG_COMMENT') !== false, 'Default comment style was not modified via lexerOptions:' . $content);
    }
}
