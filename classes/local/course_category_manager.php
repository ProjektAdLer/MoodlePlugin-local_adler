<?php

namespace local_adler\local;

use coding_exception;
use dml_exception;
use invalid_parameter_exception;
use local_adler\local\db\moodle_core_repository;
use local_adler\moodle_core;
use moodle_exception;

class course_category_manager {
    /**
     * @param string $username The username of the existing user.
     * @param string $role shortname of the role to assign to the user.
     * @param string|null $category_path The path of the category. If null or an empty string is passed, it initializes to "adler/{$username}".
     * @throws dml_exception
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function create_category_user_can_create_courses_in(string $username, string $role, string|null $category_path = Null) {
        $moodle_core_repository = new moodle_core_repository();

        // handle category path (default value and parsing)
        if ($category_path === Null || strlen($category_path) === 0) {
            $category_path = "adler/{$username}";
        }
        $category_path = new course_category_path($category_path);


        // validate input
        if (!$moodle_core_repository->get_user_id_by_username($username)) {
            throw new moodle_exception('user_not_found', 'local_adler');
        }
        if (!$moodle_core_repository->get_role_id_by_shortname($role)) {
            throw new moodle_exception('role_not_found', 'local_adler');
        }
        if ($category_path->exists()) {
            throw new moodle_exception('category_already_exists', 'local_adler');
        }


        // create category and assign user to role
        $category_id = $category_path->create();
        self::assign_user_to_role_in_category($username, $role, $category_id);

        return $category_id;
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    private static function assign_user_to_role_in_category(string $username, string $role_shortname, int $category_id): void {
        $moodle_core_repository = new moodle_core_repository();

        $user_id = $moodle_core_repository->get_user_id_by_username($username);
        $role_id = $moodle_core_repository->get_role_id_by_shortname($role_shortname);
        $context = moodle_core::context_coursecat_instance($category_id);
        moodle_core::role_assign($role_id, $user_id, $context->id);
    }
}