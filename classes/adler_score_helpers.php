<?php

namespace local_adler;

use local_logging\logger;
use moodle_exception;


class adler_score_helpers {
    /** Get adler-scores for given array of course_module ids.
     * Similar to get_completion_state_and_achieved_scores, but returns adler_score objects.
     * @param $module_ids array course_module ids
     * @param int|null $user_id If null, the current user will be used
     * @return array of adler-Scores (format: [$module_id => adler_score]), entry contains false if adler_score entry could not be created
     * @throws moodle_exception
     */
    public static function get_adler_score_objects(array $module_ids, int $user_id = null): array {
        $logger = new logger('local_adler', 'adler_score_helpers');
        $adler_scores = array();
        foreach ($module_ids as $module_id) {
            $course_module = get_coursemodule_from_id(null, $module_id, 0, false, MUST_EXIST);
            $course_module = get_fast_modinfo($course_module->course)->get_cm($course_module->id);
            try {
                $adler_scores[$module_id] = new adler_score($course_module, $user_id);
            } catch (moodle_exception $e) {
                if ($e->errorcode === 'not_an_adler_cm') {
                    $logger->info('Is adler course, but adler scoring is not enabled for cm with id ' . $module_id);
                    $adler_scores[$module_id] = false;
                } else {
                    throw $e;
                }
            }
        }
        return $adler_scores;
    }

    /** Get list with achieved scores and completion state for every given module_id.
     * Similar to get_adler_score_objects, but only returns these attributes.
     * @param array|null $module_ids only required if $adler_scores is null
     * @param int|null $user_id If null, the current user will be used
     * @param adler_score[]|null $adler_scores If null, the adler_scores will be calculated. Otherwise, the given array will be used.
     * @return array of achieved scores [0=>0.5, 1=>0.7, ...], entry contains false if score could not be calculated
     * @throws moodle_exception
     *
     * @deprecated use adler_score object directly, see {@link get_adler_score_objects}
     */
    public static function get_completion_state_and_achieved_scores(?array $module_ids, int $user_id = null, array $adler_scores = null): array {
        if ($adler_scores === null) {
            $adler_scores = static::get_adler_score_objects($module_ids, $user_id);
        }

        $achieved_scores = array();
        foreach ($adler_scores as $cmid => $adler_score) {
            if ($adler_score === false) {
                $achieved_scores[$cmid] = false;
            } else {
                $achieved_scores[$cmid] = [
                    'score' => $adler_score->get_score_by_completion_state(),
                    'completion_state' => $adler_score->get_completion_state()
                ];
            }
        }
        return $achieved_scores;
    }
}