<?php
/**
 * This script seeds the local_adler_scores_items table with random scores for all modules of a given course.
 */

use local_adler\helpers;

define('CLI_SCRIPT', true);


require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . "/clilib.php");
require_once($CFG->libdir . '/completionlib.php');


$help = "Command line tool to uninstall plugins.

Options:
    -h --help                   Print this help.
    --course-id=<course-id>     A comma seperated list of course ids to be seeded. E.g. 142,46,49
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'course-id' => false,
], [
    'h' => 'help'
]);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL.'  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($help);
    exit(0);
}

if (!$options['course-id']) {
    cli_error("You must specify a course id.");
}


/**
 * @throws dml_exception
 * @throws Exception
 */
function seed(int $course_id) {
    global $DB;

    $module_support = array(
        'supported_simple' => array('url', 'page', 'resource', 'label'),
        'supported_complex' => array('h5pactivity'),
    );

    $modules = get_course_mods($course_id);
    $completion = new completion_info(helpers::get_course_from_course_id($course_id));
    foreach ($modules as $module) {
        if (!$completion->is_enabled($module)) {
            continue;
        }
        if (in_array($module->modname, $module_support['supported_simple']) || in_array($module->modname, $module_support['supported_complex'])) {
            $scores_item_id = $DB->insert_record(
                'local_adler_scores_items',
                array('type' => 'score',
                    'cmid' => $module->id,
                    'score_min' => 0,
                    'score_max' => random_int(0, 20)),
                $returnid = true,
                $bulk = false
            );
        }
    }
}

$course_ids = explode(',', $options['course-id']);
foreach ($course_ids as $course_id) {
    seed((int) $course_id);
}

//seed_scores(142);
//seed_scores(46);
//seed_scores(49);
