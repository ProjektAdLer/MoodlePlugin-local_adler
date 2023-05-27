<?php

namespace local_adler;

use moodle_exception;
# todo refactor to exceptions/not_an_adler_cm_exception
class not_an_adler_cm_exception extends moodle_exception {
    public function __construct($link='', $a=NULL, $debuginfo=null) {
        parent::__construct('not_an_adler_cm', 'local_adler');
    }
}
