<?php

declare(strict_types=1);

namespace Chiron\Views\Tests\Fixtures;

use Twig\Extension\AbstractExtension;
use Twig_Filter;

final class CustomExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new Twig_Filter('ext_rot13', 'str_rot13'),
        ];
    }
}
