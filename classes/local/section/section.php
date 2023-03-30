<?php
namespace local_adler\local\section;


use local_adler\adler_score_helpers;
use local_adler\local\exceptions\not_an_adler_section_exception;
use local_adler\static_call_trait;
use stdClass;

class section {
    use static_call_trait;

    protected int $section_id;
    protected stdClass $section;

    /**
     * @throws not_an_adler_section_exception
     */
    public function __construct(int $section_id) {
        $this->section_id = $section_id;

        $section = $this->callStatic(db::class, 'get_adler_section', $section_id);
        if ($section === false) {
            throw new not_an_adler_section_exception();
        }
        $this->section = $section;
    }

    /** Check if user has enough points to complete this section
     * @param int $user_id moodle user id
     * @return bool true if user has completed this section, false otherwise
     */
    public function is_completed(int $user_id): bool {
        // get array of module ids for this section
        $module_ids = array_map(function($module) {
            return $module->id;
        }, $this->callStatic(db::class, 'get_course_modules_by_section_id', $this->section_id));

        // get sum of achieved scores for this user
        $achieved_scores = $this->callStatic(adler_score_helpers::class, 'get_achieved_scores', $module_ids, $user_id);
        $score_sum = array_sum($achieved_scores);

        return $score_sum >= $this->section->required_points_to_complete;
    }
}
