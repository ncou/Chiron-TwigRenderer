<?php

declare(strict_types=1);

namespace Chiron\Views\Tests\Fixtures;

class CustomExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_Filter('ext_rot13', 'str_rot13'),
        ];
    }
}
