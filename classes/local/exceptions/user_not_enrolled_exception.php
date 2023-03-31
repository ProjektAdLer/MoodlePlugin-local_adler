<?php

namespace local_adler\local\exceptions;

use moodle_exception;

class user_not_enrolled_exception extends moodle_exception {
    public function __construct($link='', $a=NULL, $debuginfo=null) {
        parent::__construct('user_not_enrolled', 'local_adler');
    }
}