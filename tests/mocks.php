<?php

namespace local_adler;
use stdClass;


global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/static_mock_framework.php');


use local_adler\lib\static_mock_utilities_trait;

class dsl_score_helpers_mock extends dsl_score_helpers {
    use static_mock_utilities_trait;
    protected static int $trait_version = 2;

    public static function get_dsl_score_objects(array $module_ids, int $user_id = null): array {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }

    public static function get_achieved_scores(?array $module_ids, int $user_id = null, array $dsl_scores = null): array {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }

    public static function get_adler_score_record(int $cmid): stdClass {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }
}