<?php

namespace local_adler;

use core\di;
use core\event\course_content_deleted;
use core\event\course_deleted;
use core\event\course_module_deleted;
use core\event\course_section_deleted;
use dml_exception;
use local_adler\local\db\adler_course_module_repository;
use local_adler\local\db\adler_course_repository;
use local_adler\local\db\adler_sections_repository;
use local_adler\local\db\moodle_core_repository;
use local_logging\logger;

defined('MOODLE_INTERNAL') || die();


class observer {
    /**
     * Observer for the event course_module_deleted.
     *
     * @param course_module_deleted $event
     * @throws dml_exception
     */
    public static function course_module_deleted(course_module_deleted $event): void {
        $adler_course_module_repository = di::get(adler_course_module_repository::class);
        $logger = new logger('local_adler', 'observer');
        $cmid = $event->objectid;

        // check if is adler course
        if (!di::get(adler_course_repository::class)->course_is_adler_course($event->courseid)) {
            return;
        }
        // check if is adler cm
        try {
            $adler_course_module_repository->get_adler_course_module_by_cmid($cmid);
        } catch (dml_exception $e) {
            return;
        }

        // delete adler cm
        $adler_course_module_repository->delete_adler_course_module_by_cmid($cmid);

        $logger->info('deleted adler cm for cmid ' . $cmid);
    }

    /**
     * @throws dml_exception
     */
    public static function course_section_deleted(course_section_deleted $event): void {
        $sectionid = $event->objectid;

        // check if is adler course
        if (!di::get(adler_course_repository::class)->course_is_adler_course($event->courseid)) {
            return;
        }

        // check if is adler section
        try {
            di::get(adler_sections_repository::class)->delete_adler_section_by_section_id($sectionid);
        } catch (dml_exception $e) {
            (new logger('local_adler', 'observer'))->debug('no adler section found for sectionid ' . $sectionid);
            return;
        }

        // delete adler section
        di::get(adler_sections_repository::class)->delete_adler_section_by_section_id($sectionid);
        (new logger('local_adler', 'observer'))->info('deleted adler section for sectionid ' . $sectionid);
    }

    /**
     * Observer for the event course_deleted.
     *
     * @param course_deleted $event
     * @throws dml_exception
     */
    public static function course_deleted(course_deleted $event): void {
        $courseid = $event->objectid;

        // check if is adler course
        if (!di::get(adler_course_repository::class)->course_is_adler_course($courseid)) {
            return;
        }

        // delete adler course
        di::get(adler_course_repository::class)->delete_adler_course_by_moodle_course_id($courseid);
        (new logger('local_adler', 'observer'))->info('deleted adler course for courseid ' . $courseid);
    }


    /** Delete all adler sections for sections that no longer exist.
     * @return array
     * @throws dml_exception
     */
    public static function delete_non_existing_adler_sections(): array {
        $logger = new logger('local_adler', 'observer');

        // get list of all existing sectioni_ds
        $sectioni_ds = array_column(
            di::get(moodle_core_repository::class)->get_all_moodle_sections(),
            'id'
        );

        $adler_sections = di::get(adler_sections_repository::class)->get_all_adler_sections();

        $deleted_sections = [];
        // if adler section section_id is not in $sectioni_ds, delete adler section
        foreach ($adler_sections as $adler_section) {
            if (!in_array($adler_section->section_id, $sectioni_ds)) {
                di::get(adler_sections_repository::class)->delete_adler_section_by_section_id($adler_section->section_id);
                $deleted_sections[] = $adler_section->section_id;
                $logger->info('deleted adler section for sectionid ' . $adler_section->section_id);
            }
        }

        return $deleted_sections;
    }


    /** Delete all adler scores for cms that no longer exist.
     * @return array
     * @throws dml_exception
     */
    public static function delete_non_existent_adler_cms(): array {
        $adler_course_module_repository = di::get(adler_course_module_repository::class);


        $logger = new logger('local_adler', 'observer');

        // get list of all existing cmids
        $cmids = array_column(
            di::get(moodle_core_repository::class)->get_all_moodle_course_modules(),
            'id'
        );

        $adler_scores = di::get(adler_course_module_repository::class)->get_all_adler_course_modules();

        $deleted_cms = [];
        // if adler score cmid is not in $cmids, delete adler score
        foreach ($adler_scores as $adler_score) {
            if (!in_array($adler_score->cmid, $cmids)) {
                $adler_course_module_repository->delete_adler_course_module_by_cmid($adler_score->cmid);
                $deleted_cms[] = $adler_score->cmid;
                $logger->info('deleted adler cm for cmid ' . $adler_score->cmid);
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