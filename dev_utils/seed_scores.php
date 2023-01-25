<?php
/**
 * This script seeds the local_adler_scores_items table with random scores for all modules of a given course.
 */

define('CLI_SCRIPT', true);


require(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/clilib.php");


function seed_scores(int $course_id) {
    global $DB;

    $module_support = array(
        'supported_simple' => array('url', 'page', 'resource'),
        'supported_complex' => array('h5pactivity'),
        'not_completable' => array('label'),
    );

    $modules = get_course_mods($course_id);
    foreach ($modules as $module) {
        if (in_array($module->modname, $module_support['supported_simple']) || in_array($module->modname, $module_support['supported_complex'])) {
            try {
                $scores_item_id = $DB->insert_record('local_adler_scores_items',
                    array('type' => 'score',
                        'cmid' => $module->id,
                        'score_min' => 0,
                        'score_max' => random_int(0, 20)),
                    $returnid = true, $bulk = false);
            } catch (Exception $e) {
                die($e);
            }
        } else {
            continue;
        }
    }
}

seed_scores(49);