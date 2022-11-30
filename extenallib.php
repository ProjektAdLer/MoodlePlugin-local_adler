<?php
require_once("$CFG->libdir/externallib.php");


class local_adler_external extends external_api {
    public static function score_primitive_learning_element_parameters() {
        return new external_function_parameters(
            array(
                'data' => new external_single_structure(
                    array(
                        'module_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
                        'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique', VALUE_DEFAULT, "default name"),
                    )
                )
            )
        );
    }

    public static function score_primitive_learning_element_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'group record id'),
                    'course' => new external_value(PARAM_TEXT, 'id of course'),
                    'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                )
            )
        );
    }

    public static function score_primitive_learning_element($data) { //Don't forget to set it as static
        global $CFG, $DB;

    }
}
