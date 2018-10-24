<?php

declare(strict_types=1);

namespace Chiron\Views\Tests\Fixtures;

class JsonHelper
{
    public static function encode($value, $options = 320)
    {
        return json_encode($value, $options);
    }
}
