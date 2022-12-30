<?php

namespace local_adler;

defined('MOODLE_INTERNAL') || die();

class helpers {
    public static function get_course_from_course_id($course_id) {
        global $DB;
        $course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
        return $course;
    }
}
