<?php
require_once("$CFG->libdir/externallib.php");
require_once($CFG->libdir . '/gradelib.php');



class local_adler_external extends external_api
{
    public static function score_primitive_learning_element_parameters()
    {
        return new external_function_parameters(
            array(
                'module_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
                'value' => new external_value(PARAM_BOOL, '1: completed, 0: not completed', VALUE_REQUIRED),
//                'data' => new external_single_structure(
//                    array(
//                        'module_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
//                        'value' => new external_value(PARAM_TEXT, '1: completed, 0: not completed', VALUE_REQUIRED),
//                    )
//                )
            )
        );
    }

    public static function score_primitive_learning_element_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id of score entry with actual grade'),
                    'scores_items_id' => new external_value(PARAM_INT, 'id of score metadata object (min/max/...)'),
                    'score' => new external_value(PARAM_TEXT, 'achieved score'),
                )
            )
        );
    }

    public static function score_primitive_learning_element($module_id, $value)
    {
        global $CFG, $DB;
        $params = self::validate_parameters(self::score_primitive_learning_element_parameters(), array('module_id'=>$module_id, 'value'=>$value));



        $course_module = external_api::call_external_function('core_course_get_course_module', array('cmid'=>$params['module_id']));
        if ($course_module['error']) {
            throw new invalid_parameter_exception('module does not exist or user does not have access to it');
        }
        $course_id = $course_module['data']['cm']['course'];

        // security stuff https://docs.moodle.org/dev/Access_API#Context_fetching
        $context = context_course::instance($course_module['data']['cm']['course']);
        self::validate_context($context);

        if(!is_enrolled($context)) {
            throw new moodle_exception("User is not enrolled in course " . $course_id);
        }


        $transaction = $DB->start_delegated_transaction(); //If an exception is thrown in the below code, all DB queries in this code will be rollback.
        // TODO: insert/update
        $transaction->allow_commit();
        // TODO: generate response object
        // TODO: return response
    }


    public static function score_h5p_learning_element_parameters()
    {
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

    public static function score_h5p_learning_element_returns()
    {
        return self::score_primitive_learning_element_returns();
    }

    public static function score_h5p_learning_element($data)
    {
        global $CFG, $DB;
        // TODO

        // external_api::call_external_function
            // https://docs.moodle.org/dev/Communication_Between_Components
    }


    public static function score_get_element_scores_parameters()
    {
        return new external_function_parameters(
            array(
                'module_ids' => array(
                    new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
                )
            )
        );
    }

    public static function score_get_element_scores_returns()
    {
        return self::score_primitive_learning_element_returns();
    }

    public static function score_get_element_scores($data)
    {
        global $CFG, $DB;
        // TODO
    }


    public static function score_get_course_scores_parameters()
    {
        return new external_function_parameters(
            array(
                'course_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
            )
        );
    }

    public static function score_get_course_scores_returns()
    {
        return self::score_primitive_learning_element_returns();
    }

    public static function score_get_course_scores($data)
    {
        global $CFG, $DB;
        // TODO
    }
}
