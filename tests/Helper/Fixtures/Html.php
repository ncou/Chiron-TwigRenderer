<?php

declare(strict_types=1);

namespace Chiron\Views\Tests\Helper\Fixtures;

class Html
{
    public static function helloWorld(): string
    {
        return '<strong>Hello world</strong>';
    }

    public static function helloWorldStringable()
    {
        return new Stringable('<strong>Hello world</strong>');
    }

    public static function helloWorldNonStringable()
    {
        return 1234.5;
    }
}

class Stringable
{
    private $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function __toString()
    {
        return $this->string;
    }
}
