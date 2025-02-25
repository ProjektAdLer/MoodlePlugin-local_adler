<?php
namespace local_adler\external;

use context_course;
use core\di;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use invalid_parameter_exception;
use local_adler\adler_score_helpers;
use local_adler\moodle_core;
use moodle_exception;
use restricted_context_exception;

class score_get_course_scores extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'course_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
            )
        );
    }

    public static function execute_returns(): external_function_parameters {
        return lib::get_adler_score_response_multiple_structure();
    }

    /**
     * @throws restricted_context_exception
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function execute($course_id): array {
        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), array('course_id' => $course_id));
        $course_id = $params['course_id'];

        // Permission check
        $context = di::get(moodle_core::class)->context_course_instance($course_id);
        static::validate_context($context);

        // get cmids of all modules in course
        $cms = get_fast_modinfo($course_id)->get_cms();
        $module_ids = array();
        foreach ($cms as $cm) {
            $module_ids[] = $cm->id;
        }

        // get scores
        $results = di::get(adler_score_helpers::class)::get_completion_state_and_achieved_scores($module_ids);

        // convert format return
        return ['data' => lib::convert_adler_score_array_format_to_response_structure($results)];
    }
}
