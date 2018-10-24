<?php

declare(strict_types=1);

namespace Chiron\Views;

/**
 * Class-proxy for static classes (warning you can't use this class to set a value for a static property)
 * Needed because you can't pass static class to Twig other way.
 */
class TwigRendererStaticClassProxy
{
    private $_staticClassName;

    /**
     * @param string $staticClassName
     */
    public function __construct($staticClassName)
    {
        $this->_staticClassName = $staticClassName;
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function __isset($property)
    {
        $class = new \ReflectionClass($this->_staticClassName);
        $staticProps = $class->getStaticProperties();
        $constants = $class->getConstants();

        return array_key_exists($property, $staticProps) || array_key_exists($property, $constants);
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        $class = new \ReflectionClass($this->_staticClassName);

        // check in the public class contants if the element exists
        foreach ($class->getReflectionConstants() as $classConstant) {
            if ($classConstant->isPublic() && $classConstant->getName() === $property) {
                return $classConstant->getValue();
            }
        }

        // else it could be a public static element, and if not found this function will throw a ReflexionException.
        return $class->getStaticPropertyValue($property);
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->_staticClassName, $method], $arguments);
    }
}
