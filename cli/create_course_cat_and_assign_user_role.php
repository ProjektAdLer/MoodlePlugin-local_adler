<?php

use local_adler\local\course_category_manager;
use local_adler\local\exceptions\exit_exception;

if (!defined('CLI_SCRIPT')) {
    define('CLI_SCRIPT', true);
}

require_once(__DIR__ . '/../../../config.php');
global $CFG;
require_once($CFG->libdir . '/clilib.php');

$help =
    "Create a new course category and grant the user permission to create adler courses in it.

Options:
--username=STRING       User name
--role=STRING           Role name
--category_path=STRING  Category path (optional)

-h, --help              Print out this help
";

// Parse command line arguments
list($options, $unrecognized) = cli_get_params(
    array(
        'username' => false,
        'role' => false,
        'category_path' => false,
        'help' => false
    ),
    array(
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_write(get_string('cliunknowoption', 'admin', $unrecognized) . "\n");
    echo $help;
    throw new exit_exception(1);
}

if (!empty($options['help'])) {
    echo $help;
} else {
    if (empty(trim($options['username']))) {
        cli_writeln('--username is required');
        throw new exit_exception(1);
    }

    if (empty(trim($options['role']))) {
        cli_writeln('--role is required');
        throw new exit_exception(1);
    }

    $username = trim($options['username']);
    $role = trim($options['role']);
    $category_path = trim($options['category_path']);


    try {
        $category_id = course_category_manager::create_category_user_can_create_courses_in($username, $role, $category_path);
    } catch (moodle_exception $e) {
        cli_writeln($e->getMessage());
        throw new exit_exception(1);
    }

    cli_writeln("Created category with ID $category_id and assigned user $username the role $role in it.");
}


