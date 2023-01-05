<?php

namespace local_adler;

use moodle_exception;

defined('MOODLE_INTERNAL') || die();

class helpers {
    public static function get_course_from_course_id($course_id) {
        global $DB;
        $course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
        return $course;
    }

    private const primitive_learning_elements = array(
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

    public static function is_primitive_learning_element($course_module):bool {
        // validate course_module format
        if (!isset($course_module->modname)) {
            throw new moodle_exception('course_module_format_not_valid', 'local_adler');
        }
        return in_array($course_module->modname, self::primitive_learning_elements);
    }
}
