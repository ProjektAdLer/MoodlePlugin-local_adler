<?php

namespace local_adler\local\db;


use dml_exception;

class adler_course_repository extends base_repository {
    /** Check if course is adler course
     * @param int $course_id moodle course id
     * @return bool true if course is adler course
     */
    public function course_is_adler_course(int $course_id): bool {
        try {
            $this->get_adler_course_by_moodle_course_id($course_id);
        } catch (dml_exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @throws dml_exception
     */
    public function get_adler_course_by_moodle_course_id(int $course_id): object {
        return $this->db->get_record('local_adler_course', ['course_id' => $course_id], '*', MUST_EXIST);
    }

    /**
     * @throws dml_exception
     */
    public function create_adler_course(object $course): int {
        return $this->db->insert_record('local_adler_course', $course);
    }

    /**
     * @throws dml_exception
     */
    public function delete_adler_course_by_moodle_course_id(int $course_id): void {
        $this->db->delete_records('local_adler_course', ['course_id' => $course_id]);
    }
}