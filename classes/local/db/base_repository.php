<?php

namespace local_adler\local\db;

abstract class base_repository {
    protected $db;

    public function __construct($db = null) {
        if (is_null($db)) {
            global $DB;
            $this->db = $DB;
        } else {
            $this->db = $db;
        }
    }
}