<?php
namespace local_adler\local\db;


use dml_exception;
use stdClass;

class adler_course_module_repository extends base_repository {
    /**
     * @param int $cmid
     * @return stdClass course_module record
     * @throws dml_exception
     */
    public function get_adler_score_record_by_cmid(int $cmid): stdClass {
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