<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . "/clilib.php");

$help = "Command line tool to uninstall plugins.

Options:
    -h --help                   Print this help.
    --course-id=<course-id>     A comma seperated list of course ids to be seeded. E.g. 142,46,49
    -c --course                 Seed course
    -s --section                Seed section
    -m --module                 Seed course module
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'course-id' => false,
    'course' => false,
    'section' => false,
    'module' => false,
], [
    'h' => 'help',
    'c' => 'course',
    's' => 'section',
    'm' => 'module',
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

$course_ids = explode(',', $options['course-id']);
foreach ($course_ids as $course_id) {
    cli_writeln(" -> Seeding course with id $course_id");

    if ($options['course']) {
        cli_writeln(" ---> Seeding course");
        shell_exec("php " . __DIR__ . "/seed/course.php --course-id=" . $options['course-id']);
    }

    if ($options['module']) {
        cli_writeln(" ---> Seeding module");
        shell_exec("php " . __DIR__ . "/seed/course_modules.php --course-id=" . $options['course-id']);
    }

    if ($options['section']) {
        cli_writeln(" ---> Seeding section");
        shell_exec("php " . __DIR__ . "/seed/section.php --course-id=" . $options['course-id']);
    }
}
