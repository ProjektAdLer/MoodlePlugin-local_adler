<?php

namespace local_adler\local\db;


use dml_exception;
use stdClass;

class moodle_core_repository extends base_repository {
    public function get_category_ids_where_user_has_role(int $user_id, int $role_id, int $context_level): array {
        return $this->db->get_fieldset_sql(
            "SELECT ctx.instanceid as category_id
                 FROM {role_assignments} ra
                 JOIN {context} ctx ON ra.contextid = ctx.id
                 WHERE ra.userid = :userid 
                 AND ra.roleid = :roleid
                 AND ctx.contextlevel = :contextlevel",
            [
                'userid' => $user_id,
                'roleid' => $role_id,
                'contextlevel' => $context_level
            ]
        );
    }

    /**
     * @throws dml_exception
     */
    public function get_role_id_by_shortname(string $shortname, int $strictness = IGNORE_MISSING): int|false {
        return (int)$this->db->get_field('role', 'id', array('shortname' => $shortname), $strictness);
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
    public function get_course_from_course_id($course_id): stdClass {
        return $this->db->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
    }

    /**
     * Get all moodle-cms for given section id. I am not aware of any moodle core function that
     * allows accessing course_modules by only the section_id.
     * @param int $section_id moodle course id
     * @return array moodle-sections for given course
     * @throws dml_exception
     */
    public function get_course_modules_by_section_id(int $section_id): array {
        return $this->db->get_records('course_modules', ['section' => $section_id]);
    }

    /**
     * @throws dml_exception
     */
    public function get_all_moodle_course_modules(): array {
        return $this->db->get_records('course_modules');
    }


    /**
     * Get moodle section by section id
     * @param int $section_id moodle section id
     * @return stdClass moodle section for given section id
     * @throws dml_exception
     */
    public function get_moodle_section(int $section_id): stdClass {
        return $this->db->get_record('course_sections', ['id' => $section_id], '*', MUST_EXIST);
    }

    /**
     * @throws dml_exception
     */
    public function get_all_moodle_sections(): array {
        return $this->db->get_records('course_sections');
    }

    /**
     * Update moodle section with given section object
     * @param stdClass $section moodle section object
     * @return stdClass updated moodle section object
     * @throws dml_exception
     */
    public function update_moodle_section(stdClass $section): stdClass {
        $this->db->update_record('course_sections', $section);
        return $this->db->get_record('course_sections', ['id' => $section->id], '*', MUST_EXIST);
    }

    /**
     * @throws dml_exception
     */
    public function get_admin_services_site_admin_token(int $user_id): stdClass {
        return $this->db->get_record(
            'external_tokens',
            [
                'userid' => $user_id,
                'externalserviceid' => $this->db->get_field('external_services', 'id', ['shortname' => 'adler_admin_service'], MUST_EXIST),
                'tokentype' => EXTERNAL_TOKEN_PERMANENT
            ],
            strictness: MUST_EXIST
        );
    }
}