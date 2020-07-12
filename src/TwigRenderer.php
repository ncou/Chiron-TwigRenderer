<?php

declare(strict_types=1);

namespace Chiron\Views;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigRenderer implements TemplateRendererInterface
{
    use AttributesTrait;
    use ExtensionTrait;

    private $extension = 'twig';

    /**
     * @var string twig namespace to use in templates
     */
    public $twigViewsNamespace = FilesystemLoader::MAIN_NAMESPACE;

    /**
     * @var FilesystemLoader
     */
    private $twigLoader;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
        $this->twigLoader = $this->twig->getLoader();
    }

    /**
     * Render the template.
     *
     * @param string $name
     * @param array  $params
     */
    public function render(string $name, array $params = []): string
    {
        $params = array_merge($this->attributes, $params);
        $name = $this->normalizeTemplate($name);

        return $this->twig->render($name, $params);
    }

    /**
     * Add a path for template.
     */
    public function addPath(string $path, string $namespace = null): void
    {
        $namespace = $namespace ?: $this->twigViewsNamespace;
        $this->twigLoader->addPath($path, $namespace);
    }

    /**
     * Get the template directories.
     *
     * @return TemplatePath[]
     */
    public function getPaths(): array
    {
        $paths = [];
        foreach ($this->twigLoader->getNamespaces() as $namespace) {
            $name = ($namespace !== $this->twigViewsNamespace) ? $namespace : null;
            foreach ($this->twigLoader->getPaths($namespace) as $path) {
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

        return $this->twigLoader->exists($template);
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

    /**
     * Return the Twig Engine.
     */
    public function twig(): Environment
    {
        return $this->twig;
    }
}
