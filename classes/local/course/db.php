<?php
namespace local_adler\local\course;

use dml_exception;
use stdClass;

class db {
    /**
     * Get adler-course with given section_id
     * @param string $uuid moodle section id
     * @return stdClass|false adler-section for given moodle section, false if not found
     * @throws dml_exception
     */
    public static function get_adler_course_by_uuid(string $uuid) {
        global $DB;
        return $DB->get_record('local_adler_course', ['uuid' => $uuid]);
    }

    /**
     * Get adler-course with given course_id
     * @throws dml_exception
     */
    public static function get_adler_course(int $course_id) {
        global $DB;
        return $DB->get_record('local_adler_course', ['course_id' => $course_id]);
    }
}