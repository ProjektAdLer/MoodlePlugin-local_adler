<?php

namespace local_adler;

use coding_exception;
use context_coursecat;

/**
 * This class contains aliases for moodle core functions to allow mocking them.
 */
class moodle_core {
    /**
     * @throws coding_exception
     */
    public function role_assign(...$args): int {
        return role_assign(...$args);
    }

    /** alias for context_coursecat::instance() */
    public function context_coursecat_instance(...$args): object {
        return context_coursecat::instance(...$args);
    }

    /** alias for get_role_contextlevels() */
    public function get_role_contextlevels(...$args): array {
        return get_role_contextlevels(...$args);
    }
}
