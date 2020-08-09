<?php

declare(strict_types=1);

namespace Chiron\Views\Extension;

use Chiron\Router\UrlGeneratorInterface;
use Chiron\Http\RequestContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Closure;

//https://github.com/slimphp/Twig-View/blob/3.x/src/TwigExtension.php
//https://github.com/drupal/drupal/blob/9.0.x/core/lib/Drupal/Core/Template/TwigExtension.php#L92
//https://github.com/symfony/twig-bridge/blob/master/Extension/RoutingExtension.php#L41

/**
 * Provides access to UrlGenerator absoluteUrlFor() and relativeUrlFor() to generate url from route name.
 */
final class RoutingExtension extends AbstractExtension
{
    /** @var UrlGeneratorInterface */
    private $generator;
    /** @var RequestContext */
    private $context;

    /**
     * @param UrlGeneratorInterface $container
     */
    public function __construct(UrlGeneratorInterface $generator, RequestContext $context)
    {
        $this->generator = $generator;
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        // TODO : changer le nom des fonctions en path() et url() ???? prévoir aussi de créer des fonctions globales pour simplifier les choses ????
        //exemple : path() - Generates a [relative] URL path given a route name and parameters.
        //exemple : url() - Generates an absolute URL given a route name and parameters.
        return [
            new TwigFunction('absoluteUrlFor', Closure::fromCallable([$this, 'absoluteUrlFor'])),
            new TwigFunction('relativeUrlFor', Closure::fromCallable([$this, 'relativeUrlFor'])),
        ];
    }

    // TODO : ajouter la phpDoc qui doit être la même que dans le fichier fastRouteRouter et que dans UrlGenerator
    private function absoluteUrlFor(string $routeName, array $substitutions = [], array $queryParams = []): string
    {
        $uri = $this->context->request()->getUri();

        return $this->generator->absoluteUrlFor($uri, $routeName, $substitutions, $queryParams);
    }

    private function relativeUrlFor(string $routeName, array $substitutions = [], array $queryParams = []): string
    {
        return $this->generator->relativeUrlFor($routeName, $substitutions, $queryParams);
    }
}
