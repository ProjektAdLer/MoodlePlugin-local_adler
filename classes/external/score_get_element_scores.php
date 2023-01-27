<?php
namespace local_adler\external;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use local_adler\dsl_score;

class score_get_element_scores extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters(
            array(
                'module_ids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
                )
            )
        );
    }

    public static function execute_returns() {
        return new external_multiple_structure(lib::get_adler_score_response_single_structure());
    }

    public static function execute(array $module_ids) {
        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), array('module_ids' => $module_ids));
        $module_ids = $params['module_ids'];

        // Permission checks not required here. If a user is not allowed to see a module, it will not be included in the result.

        // get scores
        $scores = dsl_score::get_achieved_scores($module_ids);

        // convert format return
        return lib::convert_adler_score_array_format_to_response_structure($scores);
    }
}
