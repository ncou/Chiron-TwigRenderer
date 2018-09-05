<?php

declare(strict_types=1);

namespace Chiron\Views;

use Twig_Environment as TwigEnvironment;
use Twig_Loader_Filesystem as TwigFilesystem;

class TwigRenderer implements TemplateRendererInterface
{
    use AttributesTrait;

    /**
     * @var string
     */
    private $suffix;

    /**
     * @var TwigFilesystem
     */
    protected $loader;

    /**
     * @var TwigEnvironment
     */
    protected $engine;

    public function __construct(TwigEnvironment $engine = null, string $suffix = 'html')
    {
        $this->engine = $engine ?: $this->createEngine();
        $this->loader = $this->engine->getLoader();
        $this->suffix = is_string($suffix) ? $suffix : 'html';
    }

    /**
     * Create a default Twig environment.
     */
    private function createEngine(): TwigEnvironment
    {
        $loader = new TwigFilesystem();

        return new TwigEnvironment($loader);
    }

    /**
     * Render.
     *
     * @param string $name
     * @param array  $params
     */
    public function render(string $name, array $params = []): string
    {
        // Merge parameters based on requested template name
        //$params = $this->mergeParams($name, $this->normalizeParams($params));
        $params = array_merge($this->attributes, $params);
        $name = $this->normalizeTemplate($name);
        // Merge parameters based on normalized template name
        //$params = $this->mergeParams($name, $params);
        return $this->engine->render($name, $params);
    }

    /**
     * Add a path for template.
     */
    public function addPath(string $path, string $namespace = null): void
    {
        $namespace = $namespace ?: TwigFilesystem::MAIN_NAMESPACE;
        $this->loader->addPath($path, $namespace);
    }

    /**
     * Get the template directories.
     *
     * @return TemplatePath[]
     */
    public function getPaths(): array
    {
        $paths = [];
        foreach ($this->loader->getNamespaces() as $namespace) {
            $name = ($namespace !== TwigFilesystem::MAIN_NAMESPACE) ? $namespace : null;
            foreach ($this->loader->getPaths($namespace) as $path) {
                $paths[] = new TemplatePath($path, $name);
            }
        }

        return $paths;
    }

    /**
     * Checks if the view exists.
     *
     * @param string $name Full template path or part of a template path
     *
     * @return bool True if the path exists
     */
    public function exists(string $name): bool
    {
        $name = $this->normalizeTemplate($name);

        return $this->loader->exists($name);
    }

    /**
     * Normalize namespaced template.
     *
     * Normalizes templates in the format "namespace::template" to "@namespace/template".
     */
    public function normalizeTemplate(string $template): string
    {
        $template = preg_replace('#^([^:]+)::(.*)$#', '@$1/$2', $template);
        if (! preg_match('#\.[a-z]+$#i', $template)) {
            return sprintf('%s.%s', $template, $this->suffix);
        }

        return $template;
    }
}
