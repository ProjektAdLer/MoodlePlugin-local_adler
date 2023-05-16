<?php
namespace local_adler\local\section;

use dml_exception;
use stdClass;

class db {
    /**
     * Get adler-section with given section_id
     * @param string $uuid moodle section id
     * @return stdClass|false adler-section for given moodle section, false if not found
     * @throws dml_exception
     */
    public static function get_adler_section_by_uuid(string $uuid) {
        // TODO
        global $DB;
        return $DB->get_record('local_adler_sections', ['uuid' => $uuid]);
    }

    /**
     * Get adler-section with given section_id
     * @param int $section_id moodle section id
     * @return stdClass|false adler-section for given moodle section, false if not found
     * @throws dml_exception
     */
    public static function get_adler_section(int $section_id) {
        global $DB;
        return $DB->get_record('local_adler_sections', ['section_id' => $section_id]);
    }

    /**
     * Get all adler-sections
     * @return array all adler-sections
     * @throws dml_exception
     */
    public static function get_adler_sections(): array {
        global $DB;
        return $DB->get_records('local_adler_sections');
    }


    /**
     * Get all moodle-cms for given section id. I am not aware of any moodle core function that
     * allows accessing course_modules by only the section_id.
     * @param int $section_id moodle course id
     * @return array moodle-sections for given course
     * @throws dml_exception
     */
    public static function get_course_modules_by_section_id(int $section_id): array {
        global $DB;
        return $DB->get_records('course_modules', ['section' => $section_id]);
    }

    /**
     * Delete adler_section record for given section_id
     * @param int $section_id moodle section id
     * @return bool true if successful, false if not
     * @throws dml_exception
     */
    public static function delete_adler_section_record(int $section_id): bool {
        global $DB;
        return $DB->delete_records('local_adler_sections', ['section_id' => $section_id]);
    }

    /**
     * Get moodle section by section id
     * @param int $section_id moodle section id
     * @return stdClass|false moodle section for given section id, false if not found
     * @throws dml_exception
     */
    public static function get_moodle_section(int $section_id) {
        global $DB;
        return $DB->get_record('course_sections', ['id' => $section_id]);
    }

    /**
     * Update moodle section with given section object
     * @param stdClass $section moodle section object
     * @return stdClass updated moodle section object
     * @throws dml_exception
     */
    public static function update_moodle_section(stdClass $section): stdClass {
        global $DB;
        $DB->update_record('course_sections', $section);
        return $DB->get_record('course_sections', ['id' => $section->id]);
    }
}