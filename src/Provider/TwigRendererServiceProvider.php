<?php

namespace Chiron\Views\Provider;

use Chiron\Views\TwigRenderer;
use Chiron\Views\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class TwigRendererServiceProvider
{
    /**
     * You should have in your container the config informations using the following structure :.
     *
     * 'templates' => [
     *     'extension' => 'file extension used by templates; defaults to html',
     *     'paths' => [
     *         // namespace / path pairs
     *         //
     *         // Numeric namespaces imply the default/main namespace. Paths may be
     *         // strings or arrays of string paths to associate with the namespace.
     *     ],
     * ],
     */
    public function register(ContainerInterface $container)
    {
        // config
        if (! $container->has('templates')) {
            $container['templates'] = [
                'extension' => 'html',
                'paths'     => [],
            ];
        }
        // factory
        $container[TwigRenderer::class] = function ($c) {
            $config = $c->get('templates');

            $renderer = new TwigRenderer(null, $config['extension']);

            // Add template paths
            $allPaths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : [];
            foreach ($allPaths as $namespace => $paths) {
                $namespace = is_numeric($namespace) ? null : $namespace;
                foreach ((array) $paths as $path) {
                    $renderer->addPath($path, $namespace);
                }
            }

            return $renderer;
        };
        // alias
        $container[TemplateRendererInterface::class] = function ($c) {
            return $c->get(TwigRenderer::class);
        };
    }
}
