<?php

declare(strict_types=1);

namespace Chiron\Views\Config;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Chiron\Config\AbstractInjectableConfig;
use Chiron\Config\InjectableInterface;
use Twig\Cache\CacheInterface;

class TwigConfig extends AbstractInjectableConfig
{
    protected const CONFIG_SECTION_NAME = 'twig';

    protected function getConfigSchema(): Schema
    {
        // TODO : améliorer le typage des tableaux exemple : Expect::arrayOf(Expect::string(), Expect::type(CacheInterface::class))
        return Expect::structure([
            // general options settings
            'options' => Expect::structure([
                'debug' => Expect::bool()->default(setting('debug')),
                'charset' => Expect::string()->default(setting('charset')),
                'strict_variables' => Expect::bool()->default(false),
                'autoescape' => Expect::anyOf(Expect::bool(), Expect::string(), Expect::callable())->default('name'),
                'cache' => Expect::anyOf(Expect::bool(), Expect::string(), Expect::interface(CacheInterface::class))->default(directory('@cache/twig/')),
                'auto_reload' => Expect::bool()->default(setting('debug')),
                'optimizations' => Expect::anyOf(-1, 0)->default(-1),
            ])->castTo('array'),
            // date settings
            'date' => Expect::structure([
                'format' => Expect::string()->default('F j, Y H:i'),
                'interval_format' => Expect::string()->default('%d days'),
                'timezone' => Expect::string()->default(setting('timezone')),
            ])->castTo('array'),
            // number settings
            'number_format' => Expect::structure([
                'decimals' => Expect::int()->default(0),
                'decimal_point' => Expect::string()->default('.'),
                'thousands_separator' => Expect::string()->default(','),
            ])->castTo('array'),
            // generic parameters
            'runtime_loaders' => Expect::array(), // TODO : virer la partie runtimeLoaders !!!!!
            'globals' => Expect::array(),
            'functions' => Expect::array(),
            'filters' => Expect::array(),
            'extensions' => Expect::array(),
            'lexer' => Expect::array(),
        ]);
    }

    public function getRuntimeLoaders(): array
    {
        return $this->get('runtime_loaders');
    }

    public function getOptions(): array
    {
        return $this->get('options');
    }

    public function getGlobals(): array
    {
        return $this->get('globals');
    }

    public function getFunctions(): array
    {
        return $this->get('functions');
    }

    public function getFilters(): array
    {
        return $this->get('filters');
    }

    public function getExtensions(): array
    {
        return $this->get('extensions');
    }

    public function getLexer(): array
    {
        return $this->get('lexer');
    }

    public function getDate(): array
    {
        return $this->get('date');
    }

    public function getNumberFormat(): array
    {
        return $this->get('number_format');
    }
}
