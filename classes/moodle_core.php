<?php

namespace local_adler;

use coding_exception;
use context_course;
use context_coursecat;
use context_module;
use stdClass;

/**
 * This class contains aliases for moodle core functions to allow mocking them.
 */
class moodle_core {
    /** see {@link role_assign()}
     * @throws coding_exception
     */
    public function role_assign(...$args): int {
        return role_assign(...$args);
    }

    /** alias for {@link context_coursecat::instance()} */
    public function context_coursecat_instance(...$args): object {
        return context_coursecat::instance(...$args);
    }

    /** alias for {@link context_course::instance()} */
    public function context_course_instance(...$args): object {
        return context_course::instance(...$args);
    }

    /** alias for {@link context_module::instance()} */
    public function context_module_instance(...$args): object {
        return context_module::instance(...$args);
    }

    /** alias for {@link get_role_contextlevels()} */
    public function get_role_contextlevels(...$args): array {
        return get_role_contextlevels(...$args);
    }

    /** alias for {@link get_all_roles()} */
    public static function get_all_roles(...$args): array {
        return get_all_roles(...$args);
    }

    /** alias for {@link create_role()} */
    public static function get_role(string $role_shortname): stdClass|false {
        foreach (self::get_all_roles() as $role) {
            if ($role->shortname == $role_shortname) {
                return $role;
            }
        }
        return false;
    }

    /** alias for {@link update_internal_user_password()} */
    public static function update_internal_user_password(...$args): bool {
        return update_internal_user_password(...$args);
    }
}
