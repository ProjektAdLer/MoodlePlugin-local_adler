<?php

namespace local_adler;

use dml_exception;
use invalid_parameter_exception;
use local_adler\local\course_category_manager;
use local_adler\local\exceptions\not_an_adler_section_exception;
use local_adler\local\section\section;
use local_adler\local\section\db as section_db;
use moodle_exception;

class plugin_interface {
    /** Check if section is completed
     *
     * @param int $section_id moodle section id
     * @param int $user_id moodle user id
     * @return bool true if section is completed, false otherwise
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
        return section_db::get_moodle_section($section_id)->name;
    }

    /** Create a new course category and grant the user permission to create adler courses in it.
     *
     * @param string $username The username of the existing user.
     * @param string $role shortname of the role to assign to the user.
     * @param string|null $category_path The path of the category. If null or an empty string is passed, it initializes to "adler/{$username}".
     * @return int The ID of the created category.
     * @throws dml_exception
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function create_category_user_can_create_courses_in(string $username, string $role, string|null $category_path = Null): int {
        return course_category_manager::create_category_user_can_create_courses_in($username, $role, $category_path);
    }
}