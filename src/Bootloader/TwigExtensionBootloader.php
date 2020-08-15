<?php

namespace Chiron\Views\Bootloader;

use Chiron\Boot\Directories;
use Chiron\Bootload\AbstractBootloader;
use Chiron\PublishableCollection;
use Chiron\Console\Console;
use Chiron\Views\TemplateRendererInterface;
use Twig\Extension\DebugExtension;
use Chiron\Container\FactoryInterface;
use Chiron\Views\Extension\ContainerExtension;
use Chiron\Views\Extension\RoutingExtension;
use Chiron\Router\UrlGeneratorInterface;
use Chiron\Http\RequestContext;
use Twig\Environment;

final class TwigExtensionBootloader extends AbstractBootloader
{
    public function boot(Environment $twig, FactoryInterface $factory): void
    {
        $twig->addExtension($factory->build(ContainerExtension::class));

        if (setting('debug') === true) {
            // Twig Debug extension provide access to the "dump()" function.
            $twig->addExtension($factory->build(DebugExtension::class));
        }

        // if the "http" and "router" classes are presents we enable the extension.
        if (di()->has(UrlGeneratorInterface::class) && di()->has(RequestContext::class)) {
            $twig->addExtension($factory->build(RoutingExtension::class));
        }
    }
}
