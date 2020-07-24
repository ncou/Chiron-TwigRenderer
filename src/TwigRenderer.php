<?php

declare(strict_types=1);

namespace Chiron\Views;

//https://github.com/mezzio/mezzio-twigrenderer/blob/master/src/TwigRenderer.php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigRenderer implements TemplateRendererInterface
{
    // TODO : créer une classe abstraite pour les renderer plutot que d'utiliser des trait ????
    use AttributesTrait;
    use ExtensionTrait;

    private $extension = 'html.twig';

    /**
     * @var string twig namespace to use in templates
     */
    public $twigViewsNamespace = FilesystemLoader::MAIN_NAMESPACE;

    /**
     * @var FilesystemLoader // TODO : c'est faux c'est plutot un LoaderInterface car on ne sait pas si l'utilisateur a bien créée un objet FilesystemLoader
     */
    // TODO : renommer en Loader !!!!
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
    public function addPath(string $path, ?string $namespace = null): void
    {
        // TODO : il faudrait pas utiliser le signe "??" au lieu de "?:" ????
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
        // TODO : attention ca va merder si l'utilisateur n'a pas défini le loader comme étant un objet de type FilesystemLoader, par exemple si il a redéfini le loader via la méthode ->twig() pour le transformer en pbjet ArrayLoader par exemple !!!!
        foreach ($this->twigLoader->getNamespaces() as $namespace) {
            // TODO : utiliser une constante pour "null" dans le cas ou on n'a pas utilisé de namespace !!!!!
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
