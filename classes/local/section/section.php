<?php
namespace local_adler\local\section;


use core\di;
use dml_exception;
use local_adler\adler_score_helpers;
use local_adler\local\db\adler_sections_repository;
use local_adler\local\db\moodle_core_repository;
use local_adler\local\exceptions\not_an_adler_section_exception;
use moodle_exception;
use stdClass;

class section {

    protected int $section_id;
    protected stdClass $section;

    /**
     * @throws not_an_adler_section_exception
     */
    public function __construct(int $section_id) {
        $this->section_id = $section_id;

        try {
            $section = di::get(adler_sections_repository::class)->get_adler_section($section_id);
        } catch (dml_exception $e) {
            throw new not_an_adler_section_exception();
        }

        $this->section = $section;
    }

    /** Check if user has enough points to complete this section
     * @param int $user_id moodle user id
     * @return bool true if user has completed this section, false otherwise
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function is_completed(int $user_id): bool {
        // get array of module ids for this section
        $module_ids = array_map(function($module) {
            return $module->id;
        }, di::get(moodle_core_repository::class)->get_course_modules_by_section_id($this->section_id));

        // get sum of achieved scores for this user
        $achieved_scores = di::get(adler_score_helpers::class)::get_adler_score_objects($module_ids, $user_id);
        $score_sum = array_reduce($achieved_scores, function($carry, $adler_score) {
            if ($adler_score === false) {
                return $carry;
            }
            return $carry + $adler_score->get_score_by_completion_state();
        }, 0.0);

        return $score_sum >= $this->section->required_points_to_complete;
    }
}
