<?php
namespace local_adler\local\section;

use dml_exception;
use stdClass;

class db {
    /**
     * Get adler-section with given section_id
     * @param int $section_id moodle section id
     * @return stdClass|false adler-section for given moodle section, false if not found
     * @throws dml_exception
     */
    public static function get_adler_section(int $section_id): stdClass {
        global $DB;
        return $DB->get_record('local_adler_sections', ['section_id' => $section_id]);
    }


    /**
     * Get all moodle-sections for given section id. I am not aware of any moodle core function that
     * allows accessing course_modules by only the section_id.
     * @param int $section_id moodle course id
     * @return array moodle-sections for given course
     * @throws dml_exception
     */
    public static function get_course_modules_by_section_id(int $section_id): array {
        global $DB;
        return $DB->get_records('course_modules', ['section' => $section_id]);
    }
}