<?php

namespace local_adler;

use completion_info;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Managing out score system for one course module
 */
class dsl_score {
    private object $course_module;

    private int $user_id;

    /**
     * @param object $course_module
     * @param int|null $user_id If null, the current user will be used
     */
    public function __construct(object $course_module, int $user_id = null) {
        $this->course_module = $course_module;

        if ($user_id === null) {
            global $USER;
            $this->user_id = $USER->id;
        } else {
            $this->user_id = $user_id;
        }
    }

    /** Calculates the score based on the percentage the user has achieved
     * @param float $min_score The minimum score that can be achieved.
     * @param float $max_score The maximum score that can be achieved.
     * @param float $percentage_achieved As float value between 0 and 1
     */
    private static function calculate_score(float $min_score, float $max_score, float $percentage_achieved): float {
        return ($max_score - $min_score) * $percentage_achieved + $min_score;
    }

    /** Get the score for the course module.
     * Gets the completion status and for h5p activities the achieved grade and calculates the dsl score with the values from
     * local_adler_scores_items.
     * @throws moodle_exception
     */
    public function get_score(): float {
        global $DB;

        // get dsl score metadata object
        $score_item = $DB->get_record('local_adler_scores_items', array('course_modules_id' => $this->course_module->id));
        if (!$score_item) {
            throw new moodle_exception('local_adler_scores_items not found, probably this course does not support dsl-file grading', 'local_adler');
        }

        // if course_module is a h5p activity, get achieved grade
        if ($this->course_module->modname == 'h5pactivity') {
            $h5p_grade = $DB->get_record('grade_grades', array('itemid' => $score_item->grade_item_id, 'userid' => $this->user_id));
            $h5p_grade_item = $DB->get_record('grade_items', array('id' => $score_item->grade_item_id));

            if (!$h5p_grade) {
                debugging('h5p grade not found, probably the user has not submitted the h5p activity yet -> assuming 0%', DEBUG_DEVELOPER);
                return (float)$score_item->score_min;
            }
            return self::calculate_score($score_item->score_min, $score_item->score_max, $h5p_grade->finalgrade / $h5p_grade_item->grademax);
        }

        // if course_module is not a h5p activity, get completion status
        debugging('course_module is either a primitive or an unsupported complex activity', DEBUG_ALL);

        $completion = new completion_info(helpers::get_course_from_course_id($this->course_module->course));
        $completion_status = (float)$completion->get_data($this->course_module, false, $this->user_id)->completionstate;
        // TODO: entry does not exist
        return self::calculate_score($score_item->score_min, $score_item->score_max, $completion_status);
    }
}