<?php

namespace local_adler\external;

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

use context_course;
use context_module;
use core_external\restricted_context_exception;
use dml_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use external_single_structure;
use invalid_parameter_exception;
use local_adler\local\course\db as course_db;
use local_adler\local\section\db as section_db;
use local_adler\local\course_module\db as cm_db;

class get_moodle_ids_by_uuids extends external_api {
    private static string $context_course = context_course::class;
    private static string $context_module = context_module::class;

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'element_type' => new external_value(
                    PARAM_TEXT,
                    'element type, one of cm, section, course',
                    VALUE_REQUIRED),
                'uuids' => new external_multiple_structure(
                    new external_value(
                        PARAM_TEXT,
                        'element uuid',
                        VALUE_REQUIRED),
                )
            )
        );
    }

    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'data' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'uuid' => new external_value(
                            PARAM_TEXT,
                            'element uuid',
                            VALUE_REQUIRED),
                        'moodle_id' => new external_value(
                            PARAM_INT,
                            'moodle id',
                            VALUE_REQUIRED),
                        'context_id' => new external_value(
                            PARAM_INT,
                            'context id, null for sections',
                            VALUE_REQUIRED),
                    ),
                    'moodle ids and uuid for specified element type with given uuid'
                ),
            )
        ]);
    }

    /**
     * @throws invalid_parameter_exception
     * @throws dml_exception
     * @throws restricted_context_exception
     */
    public static function execute(string $element_type, array $uuids): array {
        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), array('element_type' => $element_type, 'uuids' => $uuids));
        $element_type = $params['element_type'];
        $uuids = $params['uuids'];
        // check $element_type is one of course, section, cm
        if (!in_array($element_type, ['course', 'section', 'cm'])) {
            throw new invalid_parameter_exception('invalid element type');
        }

        // die with json serialize $uuids
//        die(json_encode($uuids));
        
        // for each uuid: check permissions and get moodle id and context id
        $data = array();
        foreach ($uuids as $uuid) {
            $moodle_id = null;
            $context_id = null;
            switch ($element_type) {
                case 'course':
                    $course = course_db::get_adler_course_by_uuid($uuid);
                    $moodle_id = $course->id;

                    $context = static::$context_course::instance($moodle_id);
                    static::validate_context($context);

                    $context_id = $context->id;
                    break;
                case 'section':
                    $section = section_db::get_adler_section_by_uuid($uuid);
                    $moodle_id = $section->id;

                    $context = static::$context_module::instance($section->course);
                    static::validate_context($context);

                    // There is no context id for sections
                    break;
                case 'cm':
                    $cm = cm_db::get_adler_course_module_by_uuid($uuid);
                    $moodle_id = $cm->cmid;

                    $context = static::$context_module::instance($moodle_id);
                    static::validate_context($context);

                    $context_id = $context->id;
                    break;
            }
            $data[] = [
                'uuid' => $uuid,
                'moodle_id' => $moodle_id,
                'context_id' => $context_id,
            ];
        }

        return ['data' => $data];
    }
}
