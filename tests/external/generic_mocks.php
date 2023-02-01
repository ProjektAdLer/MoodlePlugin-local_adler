<?php

namespace local_adler\external;

/**
 * calls, returns and exceptions are arrays, the used index corresponds to the call index. 1st func call uses index 0, 2nd func call uses index 1, ...
 */
trait static_mock_utilities_trait {
    private static array $calls = array();
    private static array $returns = array();
    private static array $exceptions = array();
    private static array $mocked_methods = array();

    public static function get_calls(string $func_name) {
        return self::$calls[$func_name];
    }

    public static function set_returns(string $func_name, array $returns) {
        if (!in_array($func_name, self::$mocked_methods)) {
            self::$mocked_methods[] = $func_name;
            self::reset_data($func_name);
        }
        self::$returns[$func_name] = $returns;
    }

    /**
     * @param array $exceptions [null, null, <exception>, null, ...], exceptions are full objects, they will be thrown like `throw $exceptions[3]`
     */
    public static function set_exceptions(string $func_name, array $exceptions) {
        if (!in_array($func_name, self::$mocked_methods)) {
            self::$mocked_methods[] = $func_name;
            self::reset_data($func_name);
        }
        self::$exceptions[$func_name] = $exceptions;
    }

    /** always call this function first
     * @param string|null $func_name if null, reset all data
     */
    public static function reset_data(string $func_name=null) {
        if ($func_name === null) {
            foreach (self::$mocked_methods as $func_name) {
                self::reset_data($func_name);
            }
        } else {
            if (!in_array($func_name, self::$mocked_methods)) {
                self::$mocked_methods[] = $func_name;
            }
            self::$calls[$func_name] = array();
            self::$returns[$func_name] = array();
            self::$exceptions[$func_name] = array();
        }
    }

    public static function get_mocked_methods() {
        return self::$mocked_methods;
    }

    /** If a method should be mocked, create that method and as only content add
     * `return static::mock_this_function(__FUNCTION__, func_get_args());`
     *
     * @param string $func_name
     * @param array $args
     * @return mixed return value of the mocked function at the given index. If no return value is set, null is returned
     */
    protected static function mock_this_function($func_name, $args) {
        if (!in_array($func_name, self::$mocked_methods)) {
            self::$mocked_methods[] = $func_name;
            self::reset_data($func_name);
        }
        self::$calls[$func_name][] = $args;
        $index = count(self::$calls[$func_name]) - 1;
        if (count(self::$exceptions[$func_name]) > 0 && self::$exceptions[$func_name][$index] !== null) {
            throw self::$exceptions[$func_name][$index];
        }
        if (count(self::$returns[$func_name]) == 0) {
            return null;
        } else {
            return self::$returns[$func_name][$index];
        }
    }
}

class dsl_score_mock_new {
    use static_mock_utilities_trait;

    public static function get_achieved_scores($module_ids) {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }
}

trait external_api_validate_context_trait_new {
    use static_mock_utilities_trait;

    public static function validate_context($context) {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }
}

class context_module_mock_new {
    use static_mock_utilities_trait;

    public static function instance($module_id) {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }
}
