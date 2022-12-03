<?php

function xmldb_local_adler_install()
{
    global $DB;

    // seed scores
    $score_type_id = $DB->insert_record('adler_scores_types', array('name' => 'score'), $returnid = true, $bulk = false);

    $module_support = array(
        'supported_simple' => array('url', 'page', 'resource'),
        'supported_complex' => array('h5pactivity'),
        'not_completable' => array('label'),
    );

    $modules = get_course_mods(2);
    $i = 0;
    foreach ($modules as $module) {
        if (in_array($module->modname, $module_support['supported_simple']) || in_array($module->modname, $module_support['supported_complex'])) {
            try {
                $scores_item_id = $DB->insert_record('adler_scores_items',
                    array('scores_types_id' => $score_type_id,
                        'course_modules_id' => $module->id,
                        'score_min' => 0,
                        'score_max' => 100),
                    $returnid = true, $bulk = false);
                if ($i % 2 == 0) {
                    $DB->insert_record('adler_scores_grades',
                        array('scores_items_id' => $score_type_id,
                            'score' => random_int(0, 100)),
                        $returnid = false, $bulk = false);
                }
                $i += 1;
            } catch (Exception $e) {
                die($e);
            }
        } else {
            continue;
        }
    }

    // seed rooms

}