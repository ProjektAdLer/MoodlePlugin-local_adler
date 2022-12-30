<?php

use local_adler\dsl_score;
use local_adler\helpers;

defined('MOODLE_INTERNAL') || die();

class local_adler_external extends external_api {
    public static function score_primitive_learning_element_parameters() {
        return new external_function_parameters(
            array(
                'module_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
                'is_completed' => new external_value(PARAM_BOOL, '1: completed, 0: not completed', VALUE_REQUIRED),
            )
        );
    }

    public static function score_primitive_learning_element_returns() {
        return new  external_single_structure(
            array(
                'score' => new external_value(PARAM_FLOAT, 'achieved (dsl-file) score'),
            )
        );
    }

    /**
     * @throws restricted_context_exception
     * @throws dml_transaction_exception
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function score_primitive_learning_element($module_id, $is_completed) {
        global $CFG, $USER;
        require_once("$CFG->libdir/completionlib.php");

        // Parameter validation
        $params = self::validate_parameters(self::score_primitive_learning_element_parameters(), array(
            'module_id' => $module_id,
            'is_completed' => $is_completed
        ));

        // create moodle course object $course
        $course_module = get_coursemodule_from_id(null, $params['module_id'], 0, false, MUST_EXIST);
        $course_id = $course_module->course;
        $course = helpers::get_course_from_course_id($course_id);

        // security stuff https://docs.moodle.org/dev/Access_API#Context_fetching
        $context = context_course::instance($course_id);
        self::validate_context($context);

        if (!is_enrolled($context)) {
            throw new moodle_exception("User is not enrolled in course " . $course_id);
        }

        // TODO: check if course_module is a primitive learning element. If it's supporting gradelib it might cause unexpected behaviour if manually setting completion state

        // update completion status
        $new_completion_state = COMPLETION_INCOMPLETE;
        if ($params['is_completed']) {
            $new_completion_state = COMPLETION_COMPLETE;
        }
        $completion = new completion_info($course);
        $completion->update_state($course_module, $new_completion_state);

        // return dsl score
        $dsl_score = new dsl_score($course_module, $USER->id);
        return [
            'score'=> $dsl_score->get_score()
        ];
    }


    public static function score_h5p_learning_element_parameters() {
        return new external_function_parameters(
            array(
                'data' => new external_single_structure(
                    array(
                        'module_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
                        'xapi' => new external_value(PARAM_TEXT, 'xapi json payload for h5p module', VALUE_REQUIRED),
                    )
                )
            )
        );
    }

    public static function score_h5p_learning_element_returns() {
        return self::score_primitive_learning_element_returns();
    }

    public static function score_h5p_learning_element($data) {
        global $CFG, $DB;
        // TODO

        // external_api::call_external_function
        // https://docs.moodle.org/dev/Communication_Between_Components
    }


    public static function score_get_element_scores_parameters() {
        return new external_function_parameters(
            array(
                'module_ids' => array(
                    new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
                )
            )
        );
    }

    public static function score_get_element_scores_returns() {
        return self::score_primitive_learning_element_returns();
    }

    public static function score_get_element_scores($data) {
        global $CFG, $DB;
        // TODO
    }


    public static function score_get_course_scores_parameters() {
        return new external_function_parameters(
            array(
                'course_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
            )
        );
    }

    public static function score_get_course_scores_returns() {
        return self::score_primitive_learning_element_returns();
    }

    public static function score_get_course_scores($data) {
        global $CFG, $DB;
        // TODO
    }
}
