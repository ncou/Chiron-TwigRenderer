<?php

declare(strict_types=1);

namespace Chiron\Views\Helper;

use Twig\Markup;
use BadMethodCallException;

/**
 * Class-proxy for static classes "call" method bbecause you can't pass static class to Twig other way.
 */
final class CallStaticClassProxy
{
    /** @var string */
    private $class;

    /** @var array */
    private $settings;

    /**
     * @param string $class     The static class name to call
     * @param array  $settings  Customisation settings for the called classname / method.
     */
    public function __construct(string $class, array $settings = [])
    {
        $this->class = $class;
        $this->settings = array_merge(['is_safe' => null, 'charset' => null], $settings);
    }

    /**
     * Call the method on the defined static class.
     *
     * Supports marking the method as safe, i.e. the returned HTML won't be escaped.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        if (! method_exists($this->class, $method)) {
            throw new BadMethodCallException(sprintf('The method "%s::%s" does not exist.', $this->class, $method));
        }

        $result  = forward_static_call_array([$this->class, $method], $arguments);

        // if the user has defined the method as safe, we "protect" the result.
        if ($this->isMethodSafe($method) && $this->isStringable($result)) {
            $result = new Markup($result, $this->settings['charset']);
        }

        return $result;
    }

    /**
     * Check if the given method is defined as "safe" in the settings.
     *
     * @param string $method
     *
     * @return bool
     */
    private function isMethodSafe(string $method): bool
    {
        if ($this->settings['is_safe'] === true) {
            // in case the "is_safe" value is a boolean (= true) this means all the class methods are "safe".
            return true;
        } elseif (is_array($this->settings['is_safe'])) {
            // we check if the actual method name is listed in the "is_safe" methods values.
            return in_array($method, $this->settings['is_safe']);
        }

        return false;
    }

    /**
     * Check if the given data is a string or could be casted as a string.
     *
     * @param mixed $data
     *
     * @return bool
     */
    private function isStringable($data): bool
    {
        return is_string($data) || method_exists($data, '__toString');
    }
}
