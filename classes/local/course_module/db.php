<?php
namespace local_adler\local\course_module;

use dml_exception;
use stdClass;

class db {
    /**
     * Get adler-section with given section_id
     * @param string $uuid moodle section id
     * @return stdClass|false adler-section for given moodle section, false if not found
     * @throws dml_exception
     */
    public static function get_adler_course_module_by_uuid(string $uuid, int $course_id) {
        global $DB;
        $adler_course_modules = $DB->get_records('local_adler_course_modules', ['uuid' => $uuid]);
        $moodle_cms_in_course = get_fast_modinfo($course_id)->get_cms();

        foreach ($adler_course_modules as $adler_course_module) {
            // check if $adler_course_module is in $moodle_cms_in_course
            foreach ($moodle_cms_in_course as $moodle_course_module) {
                if ($moodle_course_module->id == $adler_course_module->cmid) {
                    return $adler_course_module;
                }
            }
        }
        throw new dml_exception('No adler-course_module found for given uuid and course_id');
    }
}