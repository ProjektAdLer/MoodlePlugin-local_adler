<?php

function xmldb_local_adler_install()
{
    global $DB;

    $course_id = 29;

    $module_support = array(
        'supported_simple' => array('url', 'page', 'resource'),
        'supported_complex' => array('h5pactivity'),
        'not_completable' => array('label'),
    );

    $modules = get_course_mods($course_id);
    $i = 0;
    foreach ($modules as $module) {
        if (in_array($module->modname, $module_support['supported_simple']) || in_array($module->modname, $module_support['supported_complex'])) {
            try {
                $scores_item_id = $DB->insert_record('local_adler_scores_items',
                    array('type' => 'score',
                        'course_modules_id' => $module->id,
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

    // seed rooms

}
