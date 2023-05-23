<?php

namespace local_adler\external;


global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/lib/horde/framework/Horde/Support/Uuid.php');  # required on some installs (bitnami moodle on phils pc), unknown why

use backup;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use restore_controller;
use restore_dbops;


class upload_course extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'mbz' => new external_value(PARAM_TEXT, 'mbz in base64 format', VALUE_REQUIRED),
            )
        );
    }

    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'data' => new external_single_structure(
                array(
                    'course_id' => new external_value(PARAM_INT, 'id of the newly created course', VALUE_REQUIRED),
                )
            )
        ]);
    }

    public static function execute($mbz): array {
        global $CFG;

        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), array(
            'mbz' => $mbz,
        ));

        // Saving file.
        $dir = make_temp_directory('wsupload');

        $filename = uniqid('wsupload', true) . '_' . time() . '.tmp';

        if (file_exists($dir . $filename)) {
            $savedfilepath = $dir . uniqid('m') . $filename;
        } else {
            $savedfilepath = $dir . $filename;
        }

        file_put_contents($savedfilepath, base64_decode($mbz));


        // restore mbz
        $categoryid = 1; // e.g. 1 == Miscellaneous
        $userdoingrestore = 2; // e.g. 2 == admin
        $courseid = restore_dbops::create_new_course('', '', $categoryid);


        $foldername = restore_controller::get_tempdir_name($courseid, $userdoingrestore);
        $fp = get_file_packer('application/vnd.moodle.backup');
        $tempdir = make_backup_temp_directory($foldername);
        $files = $fp->extract_to_pathname($savedfilepath, $tempdir);

        $controller = new restore_controller(
            $foldername,
            $courseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $userdoingrestore,
            backup::TARGET_NEW_COURSE
        );

        $plan = $controller->get_plan();

        $plan->get_tasks()[0]->get_setting('enrolments')->set_value(backup::ENROL_ALWAYS);



        $controller->execute_precheck();
        $controller->execute_plan();
        $controller->destroy();

//        // set restore option: self-enrolment
//        $course = get_course($courseid);
//        $course->enrolpassword = '';
//        $course->enrolstartdate = 0;
//        $course->enrolenddate = 0;
//        $course->enrol = 'self';
//        update_course($course);


        // delete file $savedfilepath


        return array('data' => array(
            'course_id' => $courseid,
        ));
    }
}
