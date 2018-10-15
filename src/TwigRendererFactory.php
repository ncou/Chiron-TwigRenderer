<?php

declare(strict_types=1);

namespace Chiron\Views;

use Psr\Container\ContainerInterface;

//https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/src/TwigEnvironmentFactory.php
//https://github.com/yiisoft/yii-twig/blob/master/src/ViewRenderer.php

class TwigRendererFactory
{
    /**
     * @var \Twig_Environment twig environment object that renders twig templates
     */
    private $twig;
    /**
     * @var array Twig options.
     * @see http://twig.sensiolabs.org/doc/api.html#environment-options
     */
    private $options = [];
    /**
     * @var array Global variables.
     * Keys of the array are names to call in template, values are scalar or objects or names of static classes.
     * Example: `['html' => ['class' => '\Chiron\helpers\Html'], 'debug' => CHIRON_DEBUG]`.
     * In the template you can use it like this: `{{ html.a('Login', 'site/login') | raw }}`.
     */
    private $globals = [];
    /**
     * @var array Custom functions.
     * Keys of the array are names to call in template, values are names of functions or static methods of some class.
     * Example: `['rot13' => 'str_rot13', 'a' => '\Chiron\helpers\Html::a']`.
     * In the template you can use it like this: `{{ rot13('test') }}` or `{{ a('Login', 'site/login') | raw }}`.
     */
    private $functions = [];
    /**
     * @var array Custom filters.
     * Keys of the array are names to call in template, values are names of functions or static methods of some class.
     * Example: `['rot13' => 'str_rot13', 'jsonEncode' => '\Chiron\helpers\Json::encode']`.
     * In the template you can use it like this: `{{ 'test'|rot13 }}` or `{{ model|jsonEncode }}`.
     */
    private $filters = [];
    /**
     * @var array Custom extensions.
     * Example: `['Twig_Extension_Sandbox', new \Twig_Extension_Text()]`
     */
    private $extensions = [];
    /**
     * @var array Custom runtime loaders.
     * Example: `['Twig_RuntimeLoader_Foo', new \Twig_RuntimeLoader_Bar()]`
     */
    private $runtimeLoaders = [];
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
     * @see http://twig.sensiolabs.org/doc/recipes.html#customizing-the-syntax
     */
    private $lexer = [];

    public function __invoke(ContainerInterface $container) : TwigRenderer
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = isset($config['twig']) ?? [];

// TODO : ne plus utiliser de variable privées de classe car cela ne sert à rien (sauf pour $this->twig). Pour l'instant on utilise ces constantes pour pouvoir mettre la documentation en début de classe !!!!
        $this->runtimeLoaders = $config['runtime_loaders'] ?? [];
        $this->options = $config['options'] ?? [];
        $this->globals = $config['globals'] ?? [];
        $this->functions = $config['functions'] ?? [];
        $this->filters = $config['filters'] ?? [];
        $this->extensions = $config['extensions'] ?? [];
        $this->lexer = $config['lexer'] ?? [];

        $timezone = $config['timezone'] ?? null;
        $debug    = (bool) ($this->options['debug'] ?? false);
/*
        $options = array_merge([
            'cache' => Yii::getAlias($this->cachePath),
            'charset' => Yii::$app->charset,
        ], $this->options);*/

        $loader = new \Twig_Loader_Filesystem();
        $this->twig = new \Twig_Environment($loader, $this->options);

        // Add debug extension
        if ($debug) {
            $environment->addExtension(new \Twig_Extension_Debug());
        }
        // adjust the timezone
        if (isset($timezone)) {
            $this->setTimezone($timezone);
        }
        // Adding custom globals (objects or static classes)
        if (!empty($this->runtimeLoaders)) {
            $this->addRuntimeLoaders($this->runtimeLoaders);
        }
        // Adding custom globals (objects or static classes)
        if (!empty($this->globals)) {
            $this->addGlobals($this->globals);
        }
        // Adding custom functions
        if (!empty($this->functions)) {
            $this->addFunctions($this->functions);
        }
        // Adding custom filters
        if (!empty($this->filters)) {
            $this->addFilters($this->filters);
        }
        // Adding custom extensions
        if (!empty($this->extensions)) {
            $this->addExtensions($this->extensions);
        }
        // Change lexer syntax (must be set after other settings)
        if (!empty($this->lexer)) {
            $this->setLexer($this->lexer);
        }

        return new TwigRenderer($this->twig, 'html');
    }

    private function setTimezone(string $timezone): void
    {
        if (! is_string($timezone)) {
            throw new \InvalidArgumentException('"timezone" configuration value must be a string');
        }
        try {
            $timezone = new DateTimeZone($timezone);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Unknown or invalid timezone: "%s"', $timezone));
        }
        $this->twig->getExtension(\Twig_Extension_Core::class)->setTimezone($timezone);
    }

    /**
     * Adds global objects or static classes
     * @param array $globals @see self::$globals
     */
    private function addGlobals(array $globals): void
    {
        foreach ($globals as $name => $value) {
            if (is_array($value) && isset($value['class'])) {
                $value = new TwigRendererStaticClassProxy($value['class']);
            }
            $this->twig->addGlobal($name, $value);
        }
    }
    /**
     * Adds custom functions
     * @param array $functions @see self::$functions
     */
    private function addFunctions(array $functions): void
    {
        $this->addCustom('Function', $functions);
    }
    /**
     * Adds custom filters
     * @param array $filters @see self::$filters
     */
    private function addFilters(array $filters): void
    {
        $this->addCustom('Filter', $filters);
    }
    /**
     * Adds custom extensions
     * @param array $extensions @see self::$extensions
     */
    private function addExtensions(array $extensions): void
    {
        foreach ($extensions as $extName) {
            $this->twig->addExtension(is_object($extName) ? $extName : $this->createObject($extName));
        }
    }
    /**
     * Adds runtime loaders
     * @param array $runtimeLoaders @see self::$runtimeLoaders
     */
    private function addRuntimeLoaders(array $runtimeLoaders): void
    {
        foreach ($runtimeLoaders as $loaderName) {
            $this->twig->addRuntimeLoader(is_object($loaderName) ? $loaderName : $this->createObject($loaderName));
        }
    }
    /**
     * Sets Twig lexer options to change templates syntax
     * @param array $options @see self::$lexer
     */
    private function setLexer(array $options): void
    {
        $lexer = new \Twig_Lexer($this->twig, $options);
        $this->twig->setLexer($lexer);
    }
    /**
     * Adds custom function or filter
     * @param string $classType 'Function' or 'Filter'
     * @param array $elements Parameters of elements to add
     * @throws \InvalidArgumentException
     */
    private function addCustom(string $classType, array $elements): void
    {
        $classFunction = 'Twig_Simple' . $classType;
        foreach ($elements as $name => $func) {
            $twigElement = null;
            switch ($func) {
                // Callable (including just a name of function).
                case is_callable($func):
                    $twigElement = new $classFunction($name, $func);
                    break;
                // Callable (including just a name of function) + options array.
                case is_array($func) && is_callable($func[0]):
                    $twigElement = new $classFunction($name, $func[0], (!empty($func[1]) && is_array($func[1])) ? $func[1] : []);
                    break;
                case $func instanceof \Twig_SimpleFunction || $func instanceof \Twig_SimpleFilter:
                    $twigElement = $func;
            }
            if ($twigElement !== null) {
                $this->twig->{'add'.$classType}($twigElement);
            } else {
                throw new \InvalidArgumentException("Incorrect options for \"$classType\" $name.");
            }
        }
    }

    /**
     * Creates a new object using the given configuration.
     *
     * You may view this method as an enhanced version of the `new` operator.
     * The method supports creating an object based on a class name, a configuration array or
     * an anonymous function.
     *
     * Below are some usage examples:
     *
     * ```php
     * // create an object using a class name
     * $object = Yii::createObject('yii\db\Connection');
     *
     * // create an object using a configuration array
     * $object = Yii::createObject([
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // create an object with two constructor parameters
     * $object = \Yii::createObject('MyClass', [$param1, $param2]);
     * ```
     *
     * Using [[\yii\di\Container|dependency injection container]], this method can also identify
     * dependent objects, instantiate them and inject them into the newly created object.
     *
     * @param string|array|callable $type the object type. This can be specified in one of the following forms:
     *
     * - a string: representing the class name of the object to be created
     * - a configuration array: the array must contain a `class` element which is treated as the object class,
     *   and the rest of the name-value pairs will be used to initialize the corresponding object properties
     * - a PHP callable: either an anonymous function or an array representing a class method (`[$class or $object, $method]`).
     *   The callable should return a new instance of the object being created.
     *
     * @param array $params the constructor parameters
     * @return object the created object
     * @throws InvalidConfigException if the configuration is invalid.
     * @see \yii\di\Container
     */
    public static function createObject($type, array $params = [])
    {
        if (is_string($type)) {
            return static::$container->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return static::$container->get($class, $params, $type);
        } elseif (is_callable($type, true)) {
            return static::$container->invoke($type, $params);
        } elseif (is_array($type)) {
            throw new InvalidConfigException('Object configuration must be an array containing a "class" element.');
        }
        throw new InvalidConfigException('Unsupported configuration type: ' . gettype($type));
    }









    /**
     * Load an extension.
     *
     * If the extension is a string service name, retrieves it from the container.
     *
     * If the extension is not a TwigExtensionInterface, raises an exception.
     *
     * @param string|Twig_ExtensionInterface $extension
     * @throws \InvalidArgumentException if the extension provided or
     *     retrieved does not implement TwigExtensionInterface.
     */
    private function loadExtension($extension, ContainerInterface $container) : \Twig_ExtensionInterface
    {
        // Load the extension from the container if present
        if (is_string($extension) && $container->has($extension)) {
            $extension = $container->get($extension);
        }
        if (! $extension instanceof \Twig_ExtensionInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Twig extension must be an instance of %s; "%s" given,',
                \Twig_ExtensionInterface::class,
                is_object($extension) ? get_class($extension) : gettype($extension)
            ));
        }
        return $extension;
    }

    /**
     * @param string|Twig_RuntimeLoaderInterface $runtimeLoader
     * @throws \InvalidArgumentException if a given $runtimeLoader
     *     or the service it represents is not a TwigRuntimeLoaderInterface instance.
     */
    private function loadRuntimeLoader($runtimeLoader, ContainerInterface $container) : \Twig_RuntimeLoaderInterface
    {
        // Load the runtime loader from the container
        if (is_string($runtimeLoader) && $container->has($runtimeLoader)) {
            $runtimeLoader = $container->get($runtimeLoader);
        }
        if (! $runtimeLoader instanceof \Twig_RuntimeLoaderInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Twig runtime loader must be an instance of %s; "%s" given,',
                \Twig_RuntimeLoaderInterface::class,
                is_object($runtimeLoader) ? get_class($runtimeLoader) : gettype($runtimeLoader)
            ));
        }
        return $runtimeLoader;
    }
}
