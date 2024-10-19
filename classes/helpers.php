<?php

namespace local_adler;

use core\di;
use dml_exception;
use local_adler\local\db\adler_course_repository;

class helpers {
    /** Check if course is adler course
     * @param int $course_id moodle course id
     * @return bool true if course is adler course
     */
    public static function course_is_adler_course(int $course_id): bool {
        try {
            di::get(adler_course_repository::class)->get_adler_course_by_moodle_course_id($course_id);
        } catch (dml_exception $e) {
            return false;
        }

        return true;
    }
}
