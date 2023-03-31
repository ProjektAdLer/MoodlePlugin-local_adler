<?php
/**
 * This script seeds the local_adler_sections table for a given course.
 */

use local_adler\adler_score_helpers;
use local_adler\local\section\db as section_db;

define('CLI_SCRIPT', true);


require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . "/clilib.php");


function seed(int $course_id) {
    global $DB;

    // get all sections for the course
    $sections = $DB->get_records('course_sections', array('course' => $course_id));

    // insert a record for each section
    $adler_section_ids = [];
    for ($i = 0; $i < count($sections); $i++) {
        $section_id = array_keys($sections)[$i];
        // calculate required points to complete section
        $cms_in_section = section_db::get_course_modules_by_section_id($section_id);
        $max_possible_score = 0;
        foreach ($cms_in_section as $cm) {
            try {
                $adler_cm = adler_score_helpers::get_adler_score_record($cm->id);
            } catch (\Throwable $e) {
                continue;
            }
            $max_possible_score += $adler_cm->score_max;
        }
        if ($max_possible_score == 0) {
            // no adler cm in section
            continue;
        }

        // add section to array of adler sections
        $adler_section_ids[] = $section_id;

        // Drop entry if exists and create new one
        $DB->delete_records('local_adler_sections', array('section_id' => $section_id));
        $DB->insert_record('local_adler_sections',
            array(
                'section_id' => $section_id,
                'required_points_to_complete' => round($max_possible_score * .9),
                'timecreated' => time(),
                'timemodified' => time()
            ),
            $returnid = true,
            $bulk = false
        );

        // insert room/availability condition for each section except the first one
        $sections[$section_id]->availability = null;
        if (count($adler_section_ids) == 2) { // there is one previous room
            $sections[$section_id]->availability = '{"op":"&","c":[{"type":"adler","condition":"' . $adler_section_ids[count($adler_section_ids) - 2] . '"}],"showc":[true]}';
        }
        if (count($adler_section_ids) > 2) {  // two previous rooms
            $sections[$section_id]->availability = '{"op":"&","c":[{"type":"adler","condition":"' . $adler_section_ids[count($adler_section_ids) - 2] . '^' . $adler_section_ids[count($adler_section_ids) - 3] . '"}],"showc":[true]}';
        }
        $DB->update_record('course_sections', $sections[$section_id]);
    }
}

seed(142);
