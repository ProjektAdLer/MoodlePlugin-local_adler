<?php
/**
 * This script seeds the local_adler_course table for a given course.
 */


define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . "/clilib.php");

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


function seed(int $course_id) {
    global $DB;

    // check if course exists
    $course = $DB->get_record('course', array('id' => $course_id));
    if (!$course) {
        cli_error("Course with id $course_id does not exist.");
    }

    $DB->insert_record('local_adler_course',
        array(
            'course_id' => $course_id,
            'uuid' => (string) new Horde_support_Uuid,
            'instance_uuid' => (string) new Horde_support_Uuid
        ),
        $returnid = true,
        $bulk = false
    );
}

$course_ids = explode(',', $options['course-id']);
foreach ($course_ids as $course_id) {
    seed((int) $course_id);
}
