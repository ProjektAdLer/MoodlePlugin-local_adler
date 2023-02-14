<?php
namespace local_adler\external;

use context_course;
use external_api;
use external_function_parameters;
use external_value;
use invalid_parameter_exception;
use local_adler\dsl_score;
use local_adler\dsl_score_helpers;
use moodle_exception;
use restricted_context_exception;

class score_get_course_scores extends external_api {
    protected static string $dsl_score = dsl_score::class;
    protected static string $dsl_score_helpers = dsl_score_helpers::class;
    protected static string $context_course = context_course::class;

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
        $context = static::$context_course::instance($course_id);
        static::validate_context($context);

        // get cmids of all modules in course
        $cms = get_fast_modinfo($course_id)->get_cms();
        $module_ids = array();
        foreach ($cms as $cm) {
            $module_ids[] = $cm->id;
        }

        // get scores
        $scores = static::$dsl_score_helpers::get_achieved_scores($module_ids);

        // convert format return
        return ['data' => lib::convert_adler_score_array_format_to_response_structure($scores)];
    }
}
