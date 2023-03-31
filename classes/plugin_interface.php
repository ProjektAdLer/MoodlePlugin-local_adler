<?php

namespace local_adler;

use local_adler\local\exceptions\not_an_adler_section_exception;
use local_adler\local\section\section;

class plugin_interface {
    /**
     * @throws not_an_adler_section_exception
     */
    public static function is_section_completed(int $section_id, int $user_id): bool {
        $section = new section($section_id);
        return $section->is_completed($user_id);
    }
}