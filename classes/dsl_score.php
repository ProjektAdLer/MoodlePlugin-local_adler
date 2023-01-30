<?php

namespace local_adler;

# old moodle code without proper class declaration: https://github.com/Wunderbyte-GmbH/moodle-mod_booking/issues/174#issuecomment-855760228
require_once($CFG->libdir . '/completionlib.php');

use completion_info;
use context_course;
use dml_exception;
use moodle_exception;
use Throwable;

/**
 * Managing dsl score system for one course module
 */
class dsl_score {
    private object $course_module;

    private int $user_id;

    /**
     * @param object $course_module
     * @param int|null $user_id If null, the current user will be used
     * @throws moodle_exception course_module_format_not_valid, user_not_enrolled
     */
    public function __construct(object $course_module, int $user_id = null) {
        $this->course_module = $course_module;

        if ($user_id === null) {
            global $USER;
            $this->user_id = $USER->id;
        } else {
            $this->user_id = $user_id;
        }

        // validate correct course_module format
        if (!isset($this->course_module->modname)) {
            debugging('Moodle hast different course_module formats. ' .
                'The DB-Format and the one returned by get_coursemodule_from_id().' .
                ' They are incompatible and only the last one is currently supported by this method.', DEBUG_NORMAL);
            debugging('Support for DB format can be implemented if required,' .
                ' the required fields are existing there with different names.', DEBUG_DEVELOPER);
            throw new moodle_exception('course_module_format_not_valid', 'local_adler');
        }

        // validate user is enrolled in course
        $course_context = context_course::instance($this->course_module->course);
        if (!is_enrolled($course_context, $this->user_id)) {
            throw new moodle_exception('user_not_enrolled', 'local_adler');
        }
    }

    /** Calculates the score based on the percentage the user has achieved
     * @param float $min_score The minimum score that can be achieved.
     * @param float $max_score The maximum score that can be achieved.
     * @param float $percentage_achieved As float value between 0 and 1
     */
    private static function calculate_score(float $max_score, float $percentage_achieved, float $min_score = 0): float {
        return ($max_score - $min_score) * $percentage_achieved + $min_score;
    }

    /** Calculate percentage achieved between $min and $max
     * @param float $min
     * @param float $max
     * @param float $value
     * @return float as float value between 0 and 1
     */
    private static function calculate_percentage_achieved(float $value, float $max, float $min = 0): float {
        // This approach is also used by gradebook.
        if ($value > $max) {
            $value = $max;
        }
        if ($value < $min) {
            $value = $min;
        }
        return ($value - $min) / ($max - $min);
    }

    /** Get DSL-scores for given array of course_module ids.
     * Similar to get_achieved_scores, but returns dsl_score objects.
     * @param $module_ids array course_module ids
     * @param int|null $user_id If null, the current user will be used
     * @return array of DSL-Scores (format: [$module_id => dsl_score]), entry contains false if dsl_score entry could not be created
     * @throws moodle_exception
     */
    public static function get_dsl_score_objects(array $module_ids, int $user_id = null): array {
        $dsl_scores = array();
        foreach ($module_ids as $module_id) {
            $course_module = get_coursemodule_from_id(null, $module_id, 0, false, MUST_EXIST);
            $dsl_scores[$module_id] = new dsl_score($course_module, $user_id);
        }
        return $dsl_scores;
    }

    /** Get list with achieved scores for every given module_id.
     * Similar to get_dsl_score_objects, but only returns the achieved score.
     * @param array $module_ids
     * @param int|null $user_id If null, the current user will be used
     * @return array of achieved scores [0=>0.5, 1=>0.7, ...], entry contains false if score could not be calculated
     * @throws moodle_exception
     */
    public static function get_achieved_scores(array $module_ids, int $user_id = null): array {
        $dsl_scores = static::get_dsl_score_objects($module_ids, $user_id);
        $achieved_scores = array();
        foreach ($dsl_scores as $cmid => $dsl_score) {
            try {
                $achieved_scores[$cmid] = $dsl_score->get_score();
            } catch (moodle_exception $e) {
                if ($e->errorcode === 'completion_not_enabled') {
                    debugging('Completion is not enabled for course_module with id ' . $cmid, E_NOTICE);
                    $achieved_scores[$cmid] = false;
                } else if ($e->errorcode === 'scores_items_not_found') {
                    debugging('Adler scoring not enabled for course_module with id ' . $cmid, E_NOTICE);
                    $achieved_scores[$cmid] = false;
                } else {
                    debugging('Could not get score for course_module with id ' . $cmid, E_WARNING);
                    throw $e;
                }
            }
        }
        return $achieved_scores;
    }

    /** Get course_module id
     * @return int
     */
    public function get_cmid(): int {
        return $this->course_module->id;
    }

    /** Get the score for the course module.
     * Gets the completion status and for h5p activities the achieved grade and calculates the dsl score with the values from
     * local_adler_scores_items.
     * @throws dml_exception|moodle_exception
     */
    public function get_score(): float {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        // get dsl score metadata object
        // TODO: create methods to abstract the DB access
        $score_item = $DB->get_record('local_adler_scores_items', array('cmid' => $this->course_module->id));
        if (!$score_item) {
            debugging("No score item found for course module {$this->course_module->id},'.
            ' probably this course does not support dsl-file grading", E_ERROR);
            throw new moodle_exception('scores_items_not_found', 'local_adler');
        }

        // if course_module is a h5p activity, get achieved grade
        if ($this->course_module->modname == 'h5pactivity') {
            $grading_info = grade_get_grades($this->course_module->course, 'mod', 'h5pactivity', $this->course_module->instance, $this->user_id);
            $grading_info = $grading_info->items[0];

            if ($grading_info->grades[$this->user_id]->grade === null) {
                debugging('h5p grade not found, probably the user has not submitted the h5p activity yet -> assuming 0%', DEBUG_DEVELOPER);
                $relative_grade = 0;
            } else {
                $relative_grade = static::calculate_percentage_achieved(
                    $grading_info->grades[$this->user_id]->grade,
                    $grading_info->grademax,
                    $grading_info->grademin
                );
            }
            return self::calculate_score($score_item->score_max, $relative_grade);
        }

        // if course_module is not a h5p activity, get completion status
        debugging('course_module is either a primitive or an unsupported complex activity', DEBUG_ALL);

        // get completion object
        $course = helpers::get_course_from_course_id($this->course_module->course);
        $completion = new completion_info($course);

        // check if completion is enabled for this course_module
        if (!$completion->is_enabled($this->course_module)) {
            throw new moodle_exception('completion_not_enabled', 'local_adler');
        }

        // get completion status
        $completion_status = (float)$completion->get_data($this->course_module, false, $this->user_id)->completionstate;

        return self::calculate_score($score_item->score_max, $completion_status);
    }
}
