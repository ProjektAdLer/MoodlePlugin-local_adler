<?php
require_once("$CFG->libdir/externallib.php");


class local_adler_external extends external_api
{
    public static function score_primitive_learning_element_parameters()
    {
        return new external_function_parameters(
            array(
                'data' => new external_single_structure(
                    array(
                        'module_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
                        'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique', VALUE_REQUIRED),
                    )
                )
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

    public static function score_primitive_learning_element($data)
    {
        global $CFG, $DB;
        // TODO
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

    public static function local_adler_score_get_element_scores($data)
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
