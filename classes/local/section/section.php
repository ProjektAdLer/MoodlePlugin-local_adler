<?php
namespace local_adler\local\section;


use dml_exception;
use local_adler\adler_score_helpers;
use local_adler\local\exceptions\not_an_adler_section_exception;
use moodle_exception;

class section {
    protected int $section_id;
    protected object $section;

    /**
     * @throws not_an_adler_section_exception
     * @throws dml_exception
     */
    public function __construct(int $section_id) {
        $this->section_id = $section_id;

        $this->section = db::get_adler_section($section_id);
        if ($this->section === false) {
            throw new not_an_adler_section_exception();
        }
    }

    /**
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function is_completed(int $user_id) {
        // get array of module ids for this section
        $module_ids = array_map(function($module) {
            return $module->id;
        }, db::get_course_modules_by_section_id($this->section_id));

        // get sum of achieved scores for this user
        $achieved_score = array_sum(adler_score_helpers::get_achieved_scores($module_ids, $user_id));

        return $achieved_score >= $this->section->required_points_to_complete;
    }
}
