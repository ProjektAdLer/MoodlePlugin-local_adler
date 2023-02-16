<?php

namespace local_adler;

use core\event\course_content_deleted;
use core\event\course_deleted;
use core\event\course_module_deleted;
use dml_exception;

defined('MOODLE_INTERNAL') || die();


class observer {
    protected static string $helpers = helpers::class;
    private static string $adler_score_helpers = adler_score_helpers::class;

    /**
     * Observer for the event course_module_deleted.
     *
     * @param course_module_deleted $event
     */
    public static function course_module_deleted(course_module_deleted $event) {
        debugging('course_module_deleted event triggered234234324');
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
    }
}