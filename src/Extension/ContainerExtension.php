<?php

declare(strict_types=1);

namespace Chiron\Views\Extension;

use Psr\Container\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides access to container bindings using `get` alias.
 */
final class ContainerExtension extends AbstractExtension
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [new TwigFunction('get', [$this->container, 'get'])];
    }
}
