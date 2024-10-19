<?php
namespace local_adler\local\db;


use dml_exception;
use moodle_exception;
use stdClass;

class adler_course_module_repository extends base_repository {
    /**
     * Get adler-course_module by uuid. The exact same learning element could be in multiple courses.
     * @param string $course_module_uuid
     * @param int $moodle_course_id
     * @return stdClass adler course module
     * @throws dml_exception if no adler-course_module found for given uuid or no course for given course_id
     */
    public function get_adler_course_module(string $course_module_uuid, int $moodle_course_id): stdClass {
        $adler_course_modules = $this->db->get_records('local_adler_course_modules', ['uuid' => $course_module_uuid]);
        try {
            $moodle_cms_in_course = get_fast_modinfo($moodle_course_id)->get_cms();
        } catch (moodle_exception $e) {
            throw new dml_exception('Could not get course modules for course_id ' . $moodle_course_id . ': ' . $e->getMessage());
        }

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

    /**
     * @throws dml_exception
     */
    public function create_adler_cm(object $adler_cm): int {
        return $this->db->insert_record('local_adler_course_modules', $adler_cm);
    }

    /**
     * Check if a record for the given cmid exists. This is equivalent to
     * checking if a course module is an adler course module.
     *
     * @param int $cmid
     * @return bool
     * @throws dml_exception
     */
    public function record_for_cmid_exists(int $cmid): bool {
        return $this->db->record_exists('local_adler_course_modules', array('cmid' => $cmid));
    }

    /**
     * @param int $cmid
     * @return stdClass course_module record
     * @throws dml_exception
     */
    public function get_adler_course_module_by_cmid(int $cmid): stdClass {
        return $this->db->get_record('local_adler_course_modules', array('cmid' => $cmid), '*', MUST_EXIST);
    }

    /**
     * @throws dml_exception
     */
    public function get_all_adler_course_modules(): array {
        return $this->db->get_records('local_adler_course_modules');
    }

    /**
     * @param int $cmid
     * @throws dml_exception
     */
    public function delete_adler_course_module_by_cmid(int $cmid): void {
        $this->db->delete_records('local_adler_course_modules', array('cmid' => $cmid));
    }
}