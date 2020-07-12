<?php

declare(strict_types=1);

namespace Chiron\Views\Tests;

use Chiron\Container\Container;
use Chiron\Views\Tests\Fixtures\CustomExtension;
use Chiron\Views\Tests\Fixtures\StaticAndConsts;
use Chiron\Views\TwigRenderer;
use PHPUnit\Framework\TestCase;

use Chiron\Views\Config\TwigConfig;
use Chiron\Views\TwigRendererFactory;

use Twig\Extension\CoreExtension;
use Twig\RuntimeLoader\ContainerRuntimeLoader;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigRendererFactoryTest extends TestCase
{
    private function createRenderer(array $data = []): TwigRenderer
    {
        $config = new TwigConfig($data);

        $renderer = call_user_func(new TwigRendererFactory(), $config);

        $renderer->addPath(__DIR__ . '/Fixtures');

        return $renderer;
    }

    public function testConstructor()
    {
        $renderer = $this->createRenderer();
        $this->assertInstanceOf(TwigRenderer::class, $renderer);

        $twig = $renderer->twig();
        $this->assertInstanceOf(Environment::class, $twig);
    }

    public function testStaticAndConsts()
    {
        $config['globals']['staticClass'] = ['class' => StaticAndConsts::class];

        $renderer = $this->createRenderer($config);

        $content = $renderer->render('staticAndConsts.twig');
        $this->assertContains('I am a const!', $content);
        $this->assertContains('I am a static var!', $content);
        $this->assertContains('I am a static function with param pam-param!', $content);
    }

    public function testDebug()
    {
        $config['options']['debug'] = true;

        $renderer = $this->createRenderer($config);

        $content = $renderer->render('debug.twig', ['foo' => 'bar']);
        $this->assertContains('string(3) "bar"', $content);
    }

    public function testTimezoneDefined()
    {
        $config['date']['timezone'] = 'Europe/Paris';

        date_default_timezone_set('UTC');
        $renderer = $this->createRenderer($config);

        $twig = $renderer->twig();
        $timezone = $twig->getExtension(CoreExtension::class)->getTimezone();

        $this->assertEquals('Europe/Paris', $timezone->getName());
    }

    public function testTimezoneNotDefined()
    {
        $config['date']['timezone'] = null;

        date_default_timezone_set('UTC');
        $renderer = $this->createRenderer($config);

        $twig = $renderer->twig();
        $timezone = $twig->getExtension(CoreExtension::class)->getTimezone();

        $this->assertEquals('UTC', $timezone->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTimezoneInvalidFormat()
    {
        $config['date']['timezone'] = 'Foobar';

        $renderer = $this->createRenderer($config);
    }

    public function testDateFormat()
    {
        $config['date']['format'] = 'Foo';
        $config['date']['interval_format'] = 'Bar';

        $renderer = $this->createRenderer($config);

        $twig = $renderer->twig();
        $dateFormats = $twig->getExtension(CoreExtension::class)->getDateFormat();

        $this->assertEquals('Foo', $dateFormats[0]);
        $this->assertEquals('Bar', $dateFormats[1]);
    }

    public function testNumberFormat()
    {
        $config['number_format']['decimals'] = 10;
        $config['number_format']['decimal_point'] = 'Foo';
        $config['number_format']['thousands_separator'] = 'Bar';

        $renderer = $this->createRenderer($config);

        $twig = $renderer->twig();
        $numberFormats = $twig->getExtension(CoreExtension::class)->getNumberFormat();

        $this->assertEquals(10, $numberFormats[0]);
        $this->assertEquals('Foo', $numberFormats[1]);
        $this->assertEquals('Bar', $numberFormats[2]);
    }

    public function testFunctions()
    {
        $config['functions'] = [
            'json_encode' => '\Chiron\Views\Tests\Fixtures\JsonHelper::encode',
            new TwigFunction('rot13', 'str_rot13'),
            new TwigFunction('add_*', function ($symbols, $val) {
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

        $content = $renderer->render('functions1.twig');
        $this->assertEquals($content, 'Sbbone');
        $content = $renderer->render('functions2.twig');
        $this->assertEquals($content, 'val43');
        $content = $renderer->render('functions3.twig');
        $this->assertEquals($content, 'Sbbone');
        $content = $renderer->render('functions4.twig');
        $this->assertEquals($content, 'val43');
        $content = $renderer->render('functions5.twig');
        $this->assertEquals($content, '6');
        $content = $renderer->render('functions6.twig');
        $this->assertContains('echo', $content);
        $this->assertContains('variable', $content);
    }

    public function testFilters()
    {
        $config['filters'] = [
            'string_rot13' => 'str_rot13',
            new TwigFilter('rot13', 'str_rot13'),
            new TwigFilter('add_*', function ($symbols, $val) {
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

        $content = $renderer->render('filters1.twig');
        $this->assertEquals($content, 'Sbbone');
        $content = $renderer->render('filters2.twig');
        $this->assertEquals($content, 'val42');
        $content = $renderer->render('filters3.twig');
        $this->assertEquals($content, 'Sbbone');
        $content = $renderer->render('filters4.twig');
        $this->assertEquals($content, 'val42');
        $content = $renderer->render('filters5.twig');
        $this->assertEquals($content, 'Sbbone');
    }

    public function testExtension()
    {
        $config['extensions'] = [
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

        $config['extensions'] = [
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
        $config['extensions'] = [
            CustomExtension::class,
        ];

        $renderer = $this->createRenderer($config);
    }

    public function testRuntimeLoader()
    {
        $c = new Container();
        $config['runtime_loaders'] = [
            new ContainerRuntimeLoader($c),
        ];

        $renderer = $this->createRenderer($config);

        $this->assertInstanceOf(TwigRenderer::class, $renderer);
    }

    public function testRuntimeLoaderDefinedInContainer()
    {
        $c = new Container();
        $c->set('RuntimeLoader', new ContainerRuntimeLoader($c));

        $config['runtime_loaders'] = [
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
        $config['runtime_loaders'] = [
            'RuntimeLoader',
        ];

        $renderer = $this->createRenderer($config);
    }

    public function testLexerOptions()
    {
        $config['lexer'] = [
            'tag_comment' => ['{*', '*}'],
        ];

        $renderer = $this->createRenderer($config);

        $content = $renderer->render('lexer.twig');

        $this->assertFalse(strpos($content, 'CUSTOM_LEXER_TWIG_COMMENT'), 'Custom comment lexerOptions were not applied: ' . $content);
        $this->assertTrue(strpos($content, 'DEFAULT_TWIG_COMMENT') !== false, 'Default comment style was not modified via lexerOptions:' . $content);
    }
}
