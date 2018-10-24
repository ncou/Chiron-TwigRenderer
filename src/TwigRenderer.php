<?php

declare(strict_types=1);

namespace Chiron\Views;

class TwigRenderer implements TemplateRendererInterface
{
    use AttributesTrait;
    use ExtensionTrait;

    /**
     * @var string twig namespace to use in templates
     */
    public $twigViewsNamespace = \Twig_Loader_Filesystem::MAIN_NAMESPACE;

    /**
     * @var \Twig_Loader_Filesystem
     */
    private $loader;

    /**
     * @var \Twig_Environment
     */
    private $engine;

    public function __construct(\Twig_Environment $engine)
    {
        $this->engine = $engine;
        $this->loader = $this->engine->getLoader();
    }

    /**
     * Render.
     *
     * @param string $name
     * @param array  $params
     */
    public function render(string $name, array $params = []): string
    {
        $params = array_merge($this->attributes, $params);
        $name = $this->normalizeTemplate($name);

        return $this->engine->render($name, $params);
    }

    /**
     * Add a path for template.
     */
    public function addPath(string $path, string $namespace = null): void
    {
        $namespace = $namespace ?: $this->twigViewsNamespace;
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
            $name = ($namespace !== $this->twigViewsNamespace) ? $namespace : null;
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
        $template = $this->normalizeTemplate($name);

        return $this->loader->exists($template);
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
            return sprintf('%s.%s', $template, $this->extension);
        }

        return $template;
    }
}
