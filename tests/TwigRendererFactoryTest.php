<?php

declare(strict_types=1);

namespace Chiron\Views\Tests;

use Chiron\Container\Container;
use Chiron\Views\Tests\Fixtures\StaticAndConsts;
use Chiron\Views\TwigRenderer;
use Chiron\Views\TwigRendererFactory;
use PHPUnit\Framework\TestCase;

class TwigRendererFactoryTest extends TestCase
{
    public function testConstructor()
    {
        $c = new Container();

        $renderer = call_user_func(new TwigRendererFactory(), $c);
        $this->assertInstanceOf(TwigRenderer::class, $renderer);
    }

    public function testStaticAndConsts()
    {
        $c = new Container();

        $config['twig']['globals']['staticClass'] = ['class' => StaticAndConsts::class];
        $c->set('config', $config);

        $renderer = call_user_func(new TwigRendererFactory(), $c);

        $renderer->setExtension('twig');
        $renderer->addPath(__DIR__ . '/Fixtures');

        $content = $renderer->render('staticAndConsts.twig');
        $this->assertContains('I am a const!', $content);
        $this->assertContains('I am a static var!', $content);
        $this->assertContains('I am a static function with param pam-param!', $content);
    }

    /**
     * Mocks view instance.
     *
     * @return View
     */
    protected function mockConfig()
    {
        return [
            'twig' => [
                'class'   => 'yii\twig\ViewRenderer',
                'options' => [
                    'cache' => false,
                ],
                'globals' => [
                    'pos_begin' => View::POS_BEGIN,
                ],
                'functions' => [
                    't'           => '\Yii::t',
                    'json_encode' => '\yii\helpers\Json::encode',
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
                    'callable_sum' => function ($a, $b) {
                        return $a + $b;
                    },
                ],
                'filters' => [
                    'string_rot13' => 'str_rot13',
                    new \Twig_SimpleFilter('rot13', 'str_rot13'),
                    new \Twig_SimpleFilter('add_*', function ($symbols, $val) {
                        return $val . $symbols;
                    }, ['is_safe' => ['html']]),
                    'callable_rot13' => function ($string) {
                        return str_rot13($string);
                    },
                    'callable_add_*' => function ($symbols, $val) {
                        return $val . $symbols;
                    },
                ],
                'lexerOptions' => [
                    'tag_comment' => ['{*', '*}'],
                ],
                'extensions' => [
                    '\yii\twig\html\HtmlHelperExtension',
                ],
            ],
        ];
    }
}
