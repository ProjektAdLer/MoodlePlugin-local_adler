<?php

namespace local_adler\local\exceptions;

use moodle_exception;

class not_an_adler_section_exception extends moodle_exception {
    public function __construct($link='', $a=NULL, $debuginfo=null) {
        parent::__construct('not_an_adler_section', 'local_adler');
    }
}