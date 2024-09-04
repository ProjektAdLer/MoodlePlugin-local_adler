<?php
namespace local_adler\local\db;


use dml_exception;
use stdClass;

class adler_course_module_repository extends base_repository {
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
     * @param int $cmid
     * @throws dml_exception
     */
    public function delete_adler_score_record_by_cmid(int $cmid): void {
        $this->db->delete_records('local_adler_course_modules', array('cmid' => $cmid));
    }
}