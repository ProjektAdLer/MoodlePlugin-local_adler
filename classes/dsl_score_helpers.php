<?php

namespace local_adler;

use dml_exception;
use moodle_exception;
use stdClass;

class dsl_score_helpers {
    protected static string $dsl_score_class = dsl_score::class;

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
            try {
                $dsl_scores[$module_id] = new static::$dsl_score_class($course_module, $user_id);
            } catch (moodle_exception $e) {
                if ($e->errorcode === 'not_an_adler_cm') {
                    debugging('Is adler course, but adler scoring is not enabled for cm with id ' . $module_id, E_NOTICE);
                    $dsl_scores[$module_id] = false;
                } else {
                    throw $e;
                }
            }
        }
        return $dsl_scores;
    }

    /** Get list with achieved scores for every given module_id.
     * Similar to get_dsl_score_objects, but only returns the achieved score.
     * @param array|null $module_ids only required if $dsl_scores is null
     * @param int|null $user_id If null, the current user will be used
     * @param array|null $dsl_scores If null, the dsl_scores will be calculated. Otherwise, the given array will be used.
     * @return array of achieved scores [0=>0.5, 1=>0.7, ...], entry contains false if score could not be calculated
     * @throws moodle_exception
     */
    public static function get_achieved_scores(?array $module_ids, int $user_id = null, array $dsl_scores = null): array {
        if ($dsl_scores === null) {
            $dsl_scores = static::get_dsl_score_objects($module_ids, $user_id);
        }

        $achieved_scores = array();
        foreach ($dsl_scores as $cmid => $dsl_score) {
            if ($dsl_score === false) {
                $achieved_scores[$cmid] = false;
            } else {
                $achieved_scores[$cmid] = $dsl_score->get_score();
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
            throw new moodle_exception('not_an_adler_cm', 'local_adler');
        }
        return $record;
    }
}