<?php

namespace local_adler\external;

use context_course;
use context_module;
use core\di;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use invalid_parameter_exception;
use local_adler\local\db\adler_course_module_repository;
use local_adler\local\db\adler_sections_repository;
use local_adler\local\db\moodle_core_repository;
use local_adler\local\section\db as section_db;
use local_adler\local\course_module\db as cm_db;
use local_logging\logger;

class get_element_ids_by_uuids extends external_api {
    private static string $context_course = context_course::class;
    private static string $context_module = context_module::class;

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'elements' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'course_id' => new external_value(
                                PARAM_TEXT,
                                'course id',
                                VALUE_REQUIRED
                            ),
                            'element_type' => new external_value(
                                PARAM_TEXT,
                                'element type, one of section, cm',
                                VALUE_REQUIRED
                            ),
                            'uuid' => new external_value(
                                PARAM_TEXT,
                                'element uuid',
                                VALUE_REQUIRED
                            ),
                        )
                    )
                )
            )
        );
    }

    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'data' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'course_id' => new external_value(
                            PARAM_TEXT,
                            'course id (moodle id aka "instance id")',
                            VALUE_REQUIRED),
                        'element_type' => new external_value(
                            PARAM_TEXT,
                            'element type',
                            VALUE_REQUIRED),
                        'uuid' => new external_value(
                            PARAM_TEXT,
                            'element uuid',
                            VALUE_REQUIRED),

                        'moodle_id' => new external_value(
                            PARAM_INT,
                            'element moodle id',
                            VALUE_REQUIRED),
                        'context_id' => new external_value(
                            PARAM_INT,
                            'element context id, null for section',
                            VALUE_REQUIRED),
                    ),
                    'moodle ids and uuid for specified element type with given uuid'
                ),
            )
        ]);
    }

    /**
     * @param array $elements [int $course_id, string $element_type, array $uuids]
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function execute(array $elements): array {
        $logger = new logger('local_adler', 'get_element_ids_by_uuids');

        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), array('elements' => $elements));
        $elements = $params['elements'];

        // for each uuid: check permissions and get moodle id and context id
        $data = array();
        foreach ($elements as $element) {
            $course_id = $element['course_id'];
            $element_type = $element['element_type'];
            $uuid = $element['uuid'];

            $moodle_id = null;
            $context_id = null;
            switch ($element_type) {
                case 'section':
                    try {
                        $adler_section = di::get(adler_sections_repository::class)->get_adler_section_by_uuid($uuid, $course_id);
                    } catch (dml_exception $e) {
                        throw new invalid_parameter_exception('section not found, $uuid: ' . $uuid . ', $course_id: ' . $course_id);
                    }
                    $moodle_id = $adler_section->section_id;

                    $moodle_section = di::get(moodle_core_repository::class)->get_moodle_section($moodle_id);
                    $context = static::$context_course::instance($moodle_section->course);
//                    static::validate_context($context);  // TODO: check if user is enrolled (maybe validate context on course)

                    // There is no context id for sections
                    break;
                case 'cm':
                    try {
                        $cm = di::get(adler_course_module_repository::class)->get_adler_course_module_by_uuid($uuid, $course_id);
                    } catch (dml_exception $e) {
                        $logger->debug($e->getMessage());
                        throw new invalid_parameter_exception('course module not found, $uuid: ' . $uuid . ', $course_id: ' . $course_id);
                    }
                    $moodle_id = $cm->cmid;

                    $context = static::$context_module::instance($moodle_id);
//                    static::validate_context($context); // TODO: check if user is enrolled

                    $context_id = $context->id;
                    break;
                default:
                    throw new invalid_parameter_exception('invalid element type ' . $element_type);
            }
            $data[] = [
                'course_id' => $course_id,
                'element_type' => $element_type,
                'uuid' => $uuid,

                'moodle_id' => $moodle_id,
                'context_id' => $context_id,
            ];
        }

        return ['data' => $data];
    }
}
