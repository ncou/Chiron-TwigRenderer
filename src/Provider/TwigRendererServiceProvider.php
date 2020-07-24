<?php

namespace Chiron\Views\Provider;

use Chiron\Views\TemplateRendererInterface;
use Chiron\Views\TwigRendererFactory;
use Chiron\Views\TwigRenderer;
use Psr\Container\ContainerInterface;

use Chiron\Bootload\ServiceProvider\ServiceProviderInterface;
use Chiron\Container\BindingInterface;

final class TwigRendererServiceProvider implements ServiceProviderInterface
{
    public function register(BindingInterface $container): void
    {
        $container->singleton(TemplateRendererInterface::class, new TwigRendererFactory());
    }
}
