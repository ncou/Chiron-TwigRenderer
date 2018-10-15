<?php

namespace Chiron\Views\Provider;

use Chiron\Views\TemplateRendererInterface;
use Chiron\Views\TwigRenderer;
use Chiron\Views\TwigRendererFactory;
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
        // add default config settings if not already presents in the container
        if (! $container->has('templates')) {
            $container->set('templates', [
                'extension' => 'html',
                'paths'     => [],
            ]);
        }
        // *** factories ***
        $container[TwigRendererFactory::class] = function ($c) {
            return call_user_func(new TwigRendererFactory(), $c);
        };

        $container[TwigRenderer::class] = function ($c) {
            $renderer = $c->get(TwigRendererFactory::class);

            $config = $c->get('templates');

            // Add template paths
            $allPaths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : [];
            foreach ($allPaths as $namespace => $paths) {
                $namespace = is_numeric($namespace) ? null : $namespace;
                foreach ((array) $paths as $path) {
                    $renderer->addPath($path, $namespace);
                }
            }

            // Add template file extension
            $renderer->setExtension($config['extension']);

            return $renderer;
        };
        // *** alias ***
        $container[TemplateRendererInterface::class] = function ($c) {
            return $c->get(TwigRenderer::class);
        };
    }
}
