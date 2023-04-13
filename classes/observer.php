<?php

namespace local_adler;

use core\event\course_content_deleted;
use core\event\course_deleted;
use core\event\course_module_deleted;
use core\event\course_section_deleted;
use dml_exception;
use local_adler\local\section\db as section_db;

defined('MOODLE_INTERNAL') || die();


class observer {
    protected static string $helpers = helpers::class;
    protected static string $section_db = section_db::class;
    private static string $adler_score_helpers = adler_score_helpers::class;

    /**
     * Observer for the event course_module_deleted.
     *
     * @param course_module_deleted $event
     */
    public static function course_module_deleted(course_module_deleted $event) {
        $cmid = $event->objectid;

        // check if is adler course
        if (!static::$helpers::course_is_adler_course($event->courseid)) {
            return;
        }
        // check if is adler cm
        try {
            static::$adler_score_helpers::get_adler_score_record($cmid);
        } catch (not_an_adler_cm_exception $e) {
            return;
        }

        // delete adler cm
        static::$adler_score_helpers::delete_adler_score_record($cmid);
        debugging('deleted adler cm for cmid ' . $cmid);
    }

    public static function course_section_deleted(course_section_deleted $event) {
        $sectionid = $event->objectid;

        // check if is adler course
        if (!static::$helpers::course_is_adler_course($event->courseid)) {
            return;
        }

        // check if is adler section
        if (!static::$section_db::get_adler_section($sectionid)) {
            return;
        }

        // delete adler section
        static::$section_db::delete_adler_section_record($sectionid);
        debugging('deleted adler section for sectionid ' . $sectionid);
    }

    /**
     * Observer for the event course_deleted.
     *
     * @param course_deleted $event
     */
    public static function course_deleted(course_deleted $event) {
        $courseid = $event->objectid;

        // check if is adler course
        if (!static::$helpers::course_is_adler_course($courseid)) {
            return;
        }

        // delete adler course
        static::$helpers::delete_adler_course_record($courseid);
        debugging('deleted adler course for courseid ' . $courseid);
    }


    /** Delete all adler sections for sections that no longer exist.
     * @return array
     * @throws dml_exception
     */
    public static function delete_non_existing_adler_sections(): array {
        global $DB;
        // get list of all existing sectionids
        $sectionids = $DB->get_records('course_sections', [], 'id', 'id');
        $sectionids = array_column($sectionids, 'id');

        $adler_sections = $DB->get_records('local_adler_sections', [], 'section_id', 'id, section_id');

        $deleted_sections = [];
        // if adler section section_id is not in $sectionids, delete adler section
        foreach ($adler_sections as $adler_section) {
            if (!in_array($adler_section->section_id, $sectionids)) {
                static::$section_db::delete_adler_section_record($adler_section->section_id);
                $deleted_sections[] = $adler_section->section_id;
                debugging('deleted adler section for sectionid ' . $adler_section->section_id);
            }
        }

        return $deleted_sections;
    }


    /** Delete all adler scores for cms that no longer exist.
     * @return array
     * @throws dml_exception
     */
    public static function delete_non_existent_adler_cms(): array {
        global $DB;
        // get list of all existing cmids
        $cmids = $DB->get_records('course_modules', [], 'id', 'id');
        $cmids = array_column($cmids, 'id');

        $adler_scores = $DB->get_records('local_adler_scores_items', [], 'cmid', 'id, cmid');

        $deleted_cms = [];
        // if adler score cmid is not in $cmids, delete adler score
        foreach ($adler_scores as $adler_score) {
            if (!in_array($adler_score->cmid, $cmids)) {
                static::$adler_score_helpers::delete_adler_score_record($adler_score->cmid);
                $deleted_cms[] = $adler_score->cmid;
                debugging('deleted adler cm for cmid ' . $adler_score->cmid);
            }
        }

        return $deleted_cms;
    }

    /**
     * Observer for the event course_content_deleted.
     *
     * @param course_content_deleted $event
     * @throws dml_exception
     */
    public static function course_content_deleted(course_content_deleted $event) {
        /** No event is triggered on deletion of the individual cms when an entire course is "emptied".
         * At the time this event is called, the course content is already deleted, so the IDs of the cms of this course can no longer be queried.
         * Also, this information is not attached to the $event object. So the only options I am aware of are:
         * 1) Adding a field with the course ID to the adler-score objects
         * (Moodle likes duplicate data storage, was documented somewhere).
         * 2) Search for which adler-score the cms no longer exist.
         */

        static::delete_non_existent_adler_cms();
        static::delete_non_existing_adler_sections();
    }
}