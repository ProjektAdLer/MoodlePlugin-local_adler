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


    /**
     * @param string $module name of the module
     * @param int $instance_id instance id of the module
     * @throws dml_exception if the record does not exist
     */
    public function get_grade_item(string $module, int $instance_id): object {
        return $this->db->get_record('grade_items', array('iteminstance' => $instance_id, 'itemmodule' => $module), '*', MUST_EXIST);
    }

    /**
     * @param int $grade_item_id
     * @param array $data
     * @throws dml_exception if the record does not exist
     */
    public function update_grade_item_record(int $grade_item_id, array $data): void {
        $this->db->update_record('grade_items', (object)array_merge($data, ['id' => $grade_item_id]));
    }

    /**
     * @param int $cmid
     * @param array $data
     * @throws dml_exception if the record does not exist
     */
    public function update_course_module_record(int $cmid, array $data): void {
        $this->db->update_record('course_modules', (object)array_merge($data, ['id' => $cmid]));
    }

    public function get_cms_with_module_name_by_course_id(int $course_id): array {
        return $this->db->get_records_sql(
            'SELECT cm.*, m.name AS modname
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            WHERE cm.course = ?',
            [$course_id]
        );
    }

    /**
     * @throws dml_exception
     */
    public function get_course_from_course_id($course_id) {
        return $this->db->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
    }
}