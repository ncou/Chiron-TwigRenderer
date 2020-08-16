<?php

declare(strict_types=1);

namespace Chiron\Views;

use Psr\Container\ContainerInterface;
use Chiron\Views\Config\TwigConfig;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Extension\ExtensionInterface;
use Chiron\Views\Helper\CallStaticClassProxy;
use Twig\Lexer;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Chiron\Container\FactoryInterface;
use InvalidArgumentException;

// DOCUMENTATION
//https://symfony.com/doc/current/reference/configuration/twig.html
//https://github.com/symfony/symfony-docs/blob/79f046540d414fb449caadd9adb56b6c94cabc14/reference/configuration/twig.rst

//https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/src/TwigEnvironmentFactory.php
//https://github.com/yiisoft/yii-twig/blob/master/src/ViewRenderer.php
//https://github.com/silexphp/Silex-Providers/blob/master/TwigServiceProvider.php#L40

final class TwigEngineFactory
{
    /** @var Environment */
    private $twig;

    public function __invoke(TwigConfig $config, FactoryInterface $factory): Environment
    {
        $this->twig = $this->makeTwigEnvironment($config->getOptions());

        // adjust the numbers format.
        $this->initNumberFormat($config->getNumberFormat());
        // adjust the dates format and the date timezone.
        $this->initDateSettings($config->getDate());

        $this->addExtensions($config->getExtensions(), $factory);
        $this->addFunctions($config->getFunctions());
        $this->addFilters($config->getFilters());
        $this->addGlobals($config->getGlobals());
        $this->addFacades($config->getFacades());

        // change the default lexer tokens map.
        $this->replaceLexer($config->getLexer());

        return $this->twig;
    }

    private function makeTwigEnvironment(array $options): Environment
    {
        // TODO : lui passer un tableau vide de paths + le rootPath =>     $loader = new FilesystemLoader([], directory('@root'));

        $loader = new FilesystemLoader();
        //$paths = []; // the paths are added in the ViewsBootloader class.
        //$rootPath = directory('@root'); // the rootpath value is used as prefix if the added paths are not absolute.
        //$loader = new FilesystemLoader($paths, $rootPath);

        return new Environment($loader, $options);
    }

    private function initNumberFormat(array $format): void
    {
        $decimals = $format['decimals'];
        $point = $format['decimal_point'];
        $thousands = $format['thousands_separator'];

        $this->twig->getExtension(CoreExtension::class)->setNumberFormat($decimals, $point, $thousands);
    }

    private function initDateSettings(array $settings): void
    {
        $format = $settings['format'];
        $interval = $settings['interval_format'];

        $this->twig->getExtension(CoreExtension::class)->setDateFormat($format, $interval);

        // the value timezone is nullable, and in this case the default timezone is used.
        $timezone = $settings['timezone'] ?? date_default_timezone_get();

        $this->twig->getExtension(CoreExtension::class)->setTimezone($timezone);
    }

    /**
     * Adds custom extensions.
     *
     * @param array            $extensions
     * @param FactoryInterface $factory
     */
    private function addExtensions(array $extensions, FactoryInterface $factory): void
    {
        foreach ($extensions as $extension) {
            // make the class if the extension is a classe name.
            if (is_string($extension)) {
                $extension = $factory->build($extension);
            }

            // TODO : vérifier si le assert sur le type de l'interface est encore nécessaire car si on fait la vérif dans la classe de config cela sera suffisant !!!!
            $this->assertExtension($extensionName);
            $this->twig->addExtension($extension);
        }
    }

    private function assertExtension($extension): void
    {
        if (! $extension instanceof ExtensionInterface) {
            throw new InvalidArgumentException(sprintf(
                'Twig extension must be an instance of %s; "%s" given.',
                ExtensionInterface::class,
                is_object($extension) ? get_class($extension) : gettype($extension)
            ));
        }
    }

    /**
     * Adds custom functions.
     *
     * @param array $functions
     */
    private function addFunctions(array $functions): void
    {
        $this->addCustom('Function', $functions);
    }

    /**
     * Adds custom filters.
     *
     * @param array $filters
     */
    private function addFilters(array $filters): void
    {
        $this->addCustom('Filter', $filters);
    }

    /**
     * Adds custom function or filter.
     *
     * @param string $classType 'Function' or 'Filter'
     * @param array  $elements  Parameters of elements to add
     *
     * @throws InvalidArgumentException
     */
    private function addCustom(string $classType, array $elements): void
    {
        $classFunction = '\Twig\Twig' . $classType;
        foreach ($elements as $name => $func) {
            $twigElement = null;
            switch ($func) {
                // Callable (including just a name of function).
                case is_callable($func):
                    $twigElement = new $classFunction($name, $func);

                    break;
                // Callable (including just a name of function) + options array.
                case is_array($func) && is_callable($func[0]):
                    $twigElement = new $classFunction($name, $func[0], isset($func[1]) && is_array($func[1]) ? $func[1] : []);

                    break;
                case $func instanceof TwigFunction || $func instanceof TwigFilter:
                    $twigElement = $func;

                    break;
            }

            if ($twigElement !== null) {
                $this->twig->{'add' . $classType}($twigElement);
            } else {
                throw new InvalidArgumentException("Incorrect options for \"$classType\" $name.");
            }
        }
    }

    /**
     * Adds global objects or values.
     */
    // TODO : ajouter une méthode pour vérifier si l'utilisateur n'a pas déjà ajouté cette "global" dans Twig (ca peut aussi arriver si une facade a le même nom qu'une variable globale à ajouter dans twig). Utiliser un code du style : if (in_array($name, array_keys($this->twig->getGlobals()))) then throw Exception.
    private function addGlobals(array $globals): void
    {
        foreach ($globals as $name => $value) {
            $this->twig->addGlobal($name, $value);
        }
    }

    /**
     * Adds facades (it's some static classes).
     */

    // TODO : ne pas utiliser l'index du tableau comme nom de facade, mais plutot récupérer le nom de la facade à partir du FQN de la classe php et récupérant que le nom de la classe. utiliser le bout de code suivant : return basename(str_replace('\\', '/', $class));   et ca serai bien de préfixer ce nom par "Facade.", donc par exemple si on ajoute la classe "MyClasse/Helper/Html" l'alias pour la variable global dans Twig sera "Facade.Html" pour pouvoir l'utiliser dans les templates.

    // TODO : ajouter une méthode pour vérifier si l'utilisateur n'a pas déjà ajouté cette "global" dans Twig (ca peut aussi arriver si une facade a le même nom qu'une variable globale à ajouter dans twig). Utiliser un code du style : if (in_array($name, array_keys($this->twig->getGlobals()))) then throw Exception.
    private function addFacades(array $facades): void
    {
        foreach ($facades as $name => $settings) {
            $class = $settings['class'];
            $facade = new CallStaticClassProxy($class, $settings);

            $this->twig->addGlobal($name, $facade);
        }
    }

    /**
     * Set Twig lexer options to change templates syntax.
     */
    private function replaceLexer(array $tokens): void
    {
        if ($tokens !== []) {
            $lexer = new Lexer($this->twig, $tokens);
            $this->twig->setLexer($lexer);
        }
    }
}
