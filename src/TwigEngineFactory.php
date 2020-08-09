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

// DOCUMENTATION
//https://symfony.com/doc/current/reference/configuration/twig.html
//https://github.com/symfony/symfony-docs/blob/79f046540d414fb449caadd9adb56b6c94cabc14/reference/configuration/twig.rst

//https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/src/TwigEnvironmentFactory.php
//https://github.com/yiisoft/yii-twig/blob/master/src/ViewRenderer.php
//https://github.com/silexphp/Silex-Providers/blob/master/TwigServiceProvider.php#L40

final class TwigEngineFactory
{
    /**
     * @var Environment twig environment object that renders twig templates
     */
    private $twig;

    /**
     * @var array Twig options.
     *
     * @see https://twig.symfony.com/doc/3.x/api.html#environment-options
     */
    private $options = [];

    /**
     * @var array Global variables.
     *            Keys of the array are names to call in template, values are scalar or objects.
     *            Example: `['my_key' => 'SECRET_KEY', 'my_object' => new myClass()]`.
     *            In the template you can use it like this: `{{ my_object.hello('word') | raw }}`.
     */
    private $globals = [];

    /**
     * @var array Custom functions.
     *            Keys of the array are names to call in template, values are names of functions or static methods of some class.
     *            Example: `['rot13' => 'str_rot13', 'a' => '\Chiron\helpers\Html::a']`.
     *            In the template you can use it like this: `{{ rot13('test') }}` or `{{ a('Login', 'site/login') | raw }}`.
     */
    private $functions = [];

    /**
     * @var array Custom filters.
     *            Keys of the array are names to call in template, values are names of functions or static methods of some class.
     *            Example: `['rot13' => 'str_rot13', 'jsonEncode' => '\Chiron\helpers\Json::encode']`.
     *            In the template you can use it like this: `{{ 'test'|rot13 }}` or `{{ model|jsonEncode }}`.
     */
    private $filters = [];

    /**
     * @var array Custom extensions.
     *            Example: `['\Twig\Extension\Sandbox', new \Twig\Extension\Text()]`
     */
    private $extensions = [];

    /**
     * @var array Twig lexer options.
     *
     * Example: Smarty-like syntax:
     * ```php
     * [
     *     'tag_comment'  => ['{*', '*}'],
     *     'tag_block'    => ['{', '}'],
     *     'tag_variable' => ['{$', '}']
     * ]
     * ```
     *
     * @see https://twig.symfony.com/doc/3.x/recipes.html#customizing-the-syntax
     */
    private $lexer = [];

    /**
     * @var array Settings to format the dates.
     *            Example: `['timezone' => 'Europe/Paris', 'format' => 'F j, Y H:i', 'interval_format' => '%d days']`
     */
    private $date = [];
    /**
     * @var array Settings to format the numbers.
     *            Example: `['decimals' => 2, 'decimal_point' => ',', 'thousands_separator' => ' ']`
     */
    private $number = [];

    public function __invoke(TwigConfig $config): Environment
    {
        $this->options = $config->getOptions();
        $this->facades = $config->getFacades();
        $this->globals = $config->getGlobals();
        $this->functions = $config->getFunctions();
        $this->filters = $config->getFilters();
        $this->extensions = $config->getExtensions();
        $this->lexer = $config->getLexer();

        // create the "Twig" engine.
        $this->twig = $this->makeTwigEnvironment($config->getOptions());
        // adjust the numbers format.
        $this->initNumberFormat($config->getNumberFormat());
        // adjust the dates format and the date timezone.
        $this->initDateSettings($config->getDate());

        // Change lexer syntax
        if (! empty($this->lexer)) {
            $this->setLexer($this->lexer);
        }

        if (! empty($this->facades)) {
            $this->addFacades($this->facades);
        }
        // Adding custom globals (objects or static classes)
        if (! empty($this->globals)) {
            $this->addGlobals($this->globals);
        }
        // Adding custom extensions
        if (! empty($this->extensions)) {
            $this->addExtensions($this->extensions, $container);
        }
        // Adding custom functions
        if (! empty($this->functions)) {
            $this->addFunctions($this->functions);
        }
        // Adding custom filters
        if (! empty($this->filters)) {
            $this->addFilters($this->filters);
        }

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
     * Adds custom functions.
     *
     * @param array $functions @see self::$functions
     */
    // TODO : virer les balises @see ????
    private function addFunctions(array $functions): void
    {
        $this->addCustom('Function', $functions);
    }

    /**
     * Adds custom filters.
     *
     * @param array $filters @see self::$filters
     */
    // TODO : virer les balises @see ????
    private function addFilters(array $filters): void
    {
        $this->addCustom('Filter', $filters);
    }

    /**
     * Adds custom extensions.
     *
     * @param array              $extensions @see self::$extensions
     * @param ContainerInterface $container
     */
    // TODO : virer les balises @see ????
    private function addExtensions(array $extensions, ContainerInterface $container): void
    {
        foreach ($extensions as $extName) {
            $extension = $this->loadExtension($extName, $container);
            $this->twig->addExtension($extension);
        }
    }

    /**
     * Sets Twig lexer options to change templates syntax.
     *
     * @param array $tokens @see self::$lexer
     */
    // TODO : virer les balises @see ????
    private function setLexer(array $tokens): void
    {
        $lexer = new Lexer($this->twig, $tokens);
        $this->twig->setLexer($lexer);
    }

    /**
     * Adds custom function or filter.
     *
     * @param string $classType 'Function' or 'Filter'
     * @param array  $elements  Parameters of elements to add
     *
     * @throws \InvalidArgumentException
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
                    $twigElement = new $classFunction($name, $func[0], (! empty($func[1]) && is_array($func[1])) ? $func[1] : []);

                    break;
                case $func instanceof TwigFunction || $func instanceof TwigFilter:
                    $twigElement = $func;

                    break;
            }

            if ($twigElement !== null) {
                $this->twig->{'add' . $classType}($twigElement);
            } else {
                throw new \InvalidArgumentException("Incorrect options for \"$classType\" $name.");
            }
        }
    }

    /**
     * Load an extension.
     *
     * If the extension is a string service name, retrieves it from the container.
     *
     * If the extension is not a TwigExtensionInterface, raises an exception.
     *
     * @param string|ExtensionInterface $extension
     * @param ContainerInterface              $container
     *
     * @throws \InvalidArgumentException if the extension provided or retrieved does not implement ExtensionInterface.
     */
    // TODO : il faudrait plutot lui passer un FactoryInterface car on veut juste construire des classes !!!! Et le test sur le type de l'interface = ExtensionInterface ne sera plus nécessaire il faudra le faire au niveau de la classe de Config avec une vérif sur le Expect::isType() ou Expect::isInterface()
    private function loadExtension($extension, ContainerInterface $container): ExtensionInterface
    {
        // Load the extension from the container if present
        if (is_string($extension) && $container->has($extension)) {
            $extension = $container->get($extension);
        }

        if (! $extension instanceof ExtensionInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Twig extension must be an instance of %s; "%s" given.',
                ExtensionInterface::class,
                is_object($extension) ? get_class($extension) : gettype($extension)
            ));
        }

        return $extension;
    }
}
