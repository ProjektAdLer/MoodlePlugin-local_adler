<?php
/**
 * This script seeds the local_adler_course table for a given course.
 */

define('CLI_SCRIPT', true);


require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . "/clilib.php");


function seed(int $course_id) {
    global $DB;

    $DB->insert_record('local_adler_course',
        array('course_id' => $course_id),
        $returnid = true,
        $bulk = false
    );
}

seed(46);
seed(49);
