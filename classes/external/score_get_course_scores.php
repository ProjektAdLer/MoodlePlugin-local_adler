<?php
namespace local_adler\external;

use context_course;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use local_adler\dsl_score;

class score_get_course_scores extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters(
            array(
                'course_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
            )
        );
    }

    public static function execute_returns() {
        return new external_multiple_structure(lib::get_adler_score_response_single_structure());
    }

    public static function execute($course_id) {
        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), array('course_id' => $course_id));
        $course_id = $params['course_id'];

        // Permission check
        $context = context_course::instance($course_id);
        self::validate_context($context);

        // get cmids of all modules in course
        $cms = get_fast_modinfo($course_id)->get_cms();
        $module_ids = array();
        foreach ($cms as $cm) {
            $module_ids[] = $cm->id;
        }

        // get scores
        $scores = dsl_score::get_achieved_scores($module_ids);

        // convert format return
        return lib::convert_adler_score_array_format_to_response_structure($scores);
    }
}
