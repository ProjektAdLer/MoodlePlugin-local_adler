<?php

namespace local_adler;

use core\di;
use dml_exception;
use local_adler\local\db\moodle_core_repository;
use local_adler\local\exceptions\not_an_adler_section_exception;
use local_adler\local\section\section;
use moodle_exception;

class plugin_interface {
    /** Check if section is completed
     *
     * @param int $section_id moodle section id
     * @param int $user_id moodle user id
     * @return bool true if section is completed, false otherwise
     * @throws dml_exception
     * @throws moodle_exception
     * @throws not_an_adler_section_exception
     */
    public static function is_section_completed(int $section_id, int $user_id): bool {
        $section = new section($section_id);
        return $section->is_completed($user_id);
    }

    /** Get name of section
     * @param int $section_id moodle section id
     * @return string name of section
     * @throws dml_exception
     */
    public static function get_section_name(int $section_id): string {
        return di::get(moodle_core_repository::class)->get_moodle_section($section_id)->name;
    }
}