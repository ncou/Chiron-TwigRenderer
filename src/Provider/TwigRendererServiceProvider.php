<?php

namespace Chiron\Views\Provider;

use Chiron\Views\TemplateRendererInterface;
use Chiron\Views\TwigEngineFactory;
use Chiron\Views\TwigRenderer;
use Psr\Container\ContainerInterface;
use Twig\Environment;

use Chiron\Bootload\ServiceProvider\ServiceProviderInterface;
use Chiron\Container\BindingInterface;

final class TwigRendererServiceProvider implements ServiceProviderInterface
{
    public function register(BindingInterface $container): void
    {
        $container->singleton(Environment::class, new TwigEngineFactory());
        $container->singleton(TemplateRendererInterface::class, TwigRenderer::class);
    }
}
