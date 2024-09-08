<?php

namespace local_adler\local\db;


use dml_exception;

class moodle_core_repository extends base_repository {
    /**
     * @throws dml_exception
     */
    public function get_role_id_by_shortname(string $shortname): int|false {
        return (int)$this->db->get_field('role', 'id', array('shortname' => $shortname));
    }

    /**
     * @throws dml_exception
     */
    public function get_user_id_by_username(string $username): int|false {
        return (int)$this->db->get_field('user', 'id', array('username' => $username));
    }
}