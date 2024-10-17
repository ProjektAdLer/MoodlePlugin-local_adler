<?php
namespace local_adler\local\db;


use dml_exception;

class adler_sections_repository extends base_repository {
    /**
     * @throws dml_exception
     */
    public function create_adler_section(object $section): int {
        return $this->db->insert_record('local_adler_sections', $section);
    }
}