<?php

declare(strict_types=1);

namespace Chiron\Views\Provider;

use Chiron\Bootload\ServiceProvider\ServiceProviderInterface;
use Chiron\Container\BindingInterface;
use Chiron\Views\TemplateRendererInterface;
use Chiron\Views\TwigEngineFactory;
use Chiron\Views\TwigRenderer;
use Twig\Environment;

final class TwigRendererServiceProvider implements ServiceProviderInterface
{
    public function register(BindingInterface $container): void
    {
        $container->singleton(Environment::class, new TwigEngineFactory());
        $container->singleton(TemplateRendererInterface::class, TwigRenderer::class);
    }
}
