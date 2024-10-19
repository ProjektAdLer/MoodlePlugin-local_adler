<?php

namespace local_adler;

use core\di;
use dml_exception;
use local_adler\local\db\adler_course_repository;
use moodle_exception;

class helpers {
    /** Check if course is adler course
     * @param int $course_id moodle course id
     * @return bool true if course is adler course
     */
    public static function course_is_adler_course(int $course_id): bool {
        try {
            di::get(adler_course_repository::class)->get_adler_course_by_moodle_course_id($course_id);
        } catch (dml_exception $e) {
            return false;
        }

        return true;
    }

    private const PRIMITIVE_LEARNING_ELEMENTS  = array(
        'book',
        'resource',
        'imscp',
        'url',
        'label',
        'page',
        'folder',
    );
//    private $gradelib_learning_elements = array(
//        'h5pactivity',
//        'lti',
//        'screen',
//        'h5pactivity',
//        'lesson',
//        'quiz',
//        'scorm'
//    );

    /**
     * @throws moodle_exception
     */
    public static function is_primitive_learning_element($course_module): bool {
        // validate course_module format
        if (!isset($course_module->modname)) {
            throw new moodle_exception('course_module_format_not_valid', 'local_adler');
        }
        return in_array($course_module->modname, self::PRIMITIVE_LEARNING_ELEMENTS);
    }
}
