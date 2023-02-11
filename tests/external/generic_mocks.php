<?php

namespace local_adler\external;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/static_mock_framework.php');

use local_adler\lib\static_mock_utilities_trait;

class dsl_score_mock_new {
    use static_mock_utilities_trait;
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

class context_course_mock_new {
    use static_mock_utilities_trait;

    public static function instance($module_id) {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }
}


