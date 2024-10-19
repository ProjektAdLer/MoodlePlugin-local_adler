<?php

namespace local_adler\local\db;


use core\di;
use dml_exception;
use stdClass;

class adler_sections_repository extends base_repository {
    /**
     * @throws dml_exception
     */
    public function create_adler_section(object $section): int {
        return $this->db->insert_record('local_adler_sections', $section);
    }

    /**
     * Get adler-section by adler section uuid and moodle course_id
     * @param string $adler_section_uuid
     * @param int $moodle_course_id
     * @return stdClass adler-section for given moodle section
     * @throws dml_exception
     */
    public function get_adler_section_by_uuid(string $adler_section_uuid, int $moodle_course_id): stdClass {
        $adler_sections = $this->db->get_records('local_adler_sections', ['uuid' => $adler_section_uuid]);

        foreach ($adler_sections as $adler_section) {
            // get moodle section
            $moodle_section = di::get(moodle_core_repository::class)->get_moodle_section($adler_section->section_id);
            if ($moodle_section->course == $moodle_course_id) {
                return $adler_section;
            }
        }
        throw new dml_exception('No adler-section found for given uuid and course_id');
    }

    /**
     * Get adler-section with given section_id
     * @param int $section_id moodle section id
     * @return stdClass adler-section for given moodle section, false if not found
     * @throws dml_exception
     */
    public function get_adler_section(int $section_id): stdClass {
        return $this->db->get_record('local_adler_sections', ['section_id' => $section_id], '*', MUST_EXIST);
    }

    /**
     * Get all adler-sections
     * @return array all adler-sections
     * @throws dml_exception
     */
    public function get_all_adler_sections(): array {
        return $this->db->get_records('local_adler_sections');
    }


    /**
     * Delete adler_section record for given section_id
     * @param int $section_id moodle section id
     * @return bool true if successful, false if not
     * @throws dml_exception
     */
    public function delete_adler_section_by_section_id(int $section_id): bool {
        return $this->db->delete_records('local_adler_sections', ['section_id' => $section_id]);
    }
}