<?php

namespace local_adler;

use dml_exception;
use moodle_exception;
use stdClass;

global $CFG;
require_once($CFG->dirroot . '/local/adler/classes/exceptions.php');

class adler_score_helpers {
    protected static string $adler_score_class = adler_score::class;

    /** Get adler-scores for given array of course_module ids.
     * Similar to get_achieved_scores, but returns adler_score objects.
     * @param $module_ids array course_module ids
     * @param int|null $user_id If null, the current user will be used
     * @return array of adler-Scores (format: [$module_id => adler_score]), entry contains false if adler_score entry could not be created
     * @throws moodle_exception
     */
    public static function get_adler_score_objects(array $module_ids, int $user_id = null): array {
        $adler_scores = array();
        foreach ($module_ids as $module_id) {
            $course_module = get_coursemodule_from_id(null, $module_id, 0, false, MUST_EXIST);
            try {
                $adler_scores[$module_id] = new static::$adler_score_class($course_module, $user_id);
            } catch (moodle_exception $e) {
                if ($e->errorcode === 'not_an_adler_cm') {
                    debugging('Is adler course, but adler scoring is not enabled for cm with id ' . $module_id, E_NOTICE);
                    $adler_scores[$module_id] = false;
                } else {
                    throw $e;
                }
            }
        }
        return $adler_scores;
    }

    /** Get list with achieved scores for every given module_id.
     * Similar to get_adler_score_objects, but only returns the achieved score.
     * @param array|null $module_ids only required if $adler_scores is null
     * @param int|null $user_id If null, the current user will be used
     * @param array|null $adler_scores If null, the adler_scores will be calculated. Otherwise, the given array will be used.
     * @return array of achieved scores [0=>0.5, 1=>0.7, ...], entry contains false if score could not be calculated
     * @throws moodle_exception
     */
    public static function get_achieved_scores(?array $module_ids, int $user_id = null, array $adler_scores = null): array {
        if ($adler_scores === null) {
            $adler_scores = static::get_adler_score_objects($module_ids, $user_id);
        }

        $achieved_scores = array();
        foreach ($adler_scores as $cmid => $adler_score) {
            if ($adler_score === false) {
                $achieved_scores[$cmid] = false;
            } else {
                $achieved_scores[$cmid] = $adler_score->get_score();
            }
        }
        return $achieved_scores;
    }

    /** Get adler score record (local_adler_scores_items) for course module.
     * @param int $cmid
     * @return stdClass
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_adler_score_record(int $cmid): stdClass {
        global $DB;
        $record = $DB->get_record('local_adler_scores_items', array('cmid' => $cmid));
        if (!$record) {
            debugging('Course module with id ' . $cmid . ' is not an adler course module', E_NOTICE);
            throw new not_an_adler_cm_exception();
        }
        return $record;
    }

    /** Delete adler score record (local_adler_scores_items).
     * @param int $cmid
     * @throws dml_exception
     */
    public static function delete_adler_score_record(int $cmid): void {
        global $DB;
        $DB->delete_records('local_adler_scores_items', array('cmid' => $cmid));
    }
}