<?php
/**
 * This script seeds the local_adler_sections table for a given course.
 */

define('CLI_SCRIPT', true);


require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . "/clilib.php");


function seed(int $course_id) {
    global $DB;

    // get all sections for the course
    $sections = $DB->get_records('course_sections', array('course' => $course_id));

    // insert a record for each section
    foreach ($sections as $section) {
        $DB->insert_record('local_adler_sections',
            array(
                'section_id' => $section->id,
                'required_points_to_complete' => 100,
                'timecreated' => time(),
                'timemodified' => time()
            ),
            $returnid = true,
            $bulk = false
        );
    }
}

seed(49);
