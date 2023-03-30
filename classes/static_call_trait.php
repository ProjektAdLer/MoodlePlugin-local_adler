<?php

namespace local_adler;


/**
 * Trait for static method calls for easier testing
 *
 * Only use once in a class hierarchy
 *
 * see: https://pagemachine.de/blog/mocking-static-method-calls
 */
trait static_call_trait {
    /**
     * Performs a static method call
     *
     * @param string $classAndMethod Name of the class
     * @param string $methodName Name of the method
     * @param mixed $parameter,... Parameters to the method
     * @return mixed
     */
    protected function callStatic($className, $methodName) {
        $parameters = func_get_args();
        $parameters = array_slice($parameters, 2); // Remove $className and $methodName

        return call_user_func_array($className . '::' . $methodName, $parameters);
    }
}