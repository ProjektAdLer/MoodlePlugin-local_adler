<?php

namespace local_adler\external;


global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/lib/horde/framework/Horde/Support/Uuid.php');  # required on some installs (bitnami moodle on phils pc), unknown why

use backup;
use Exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use local_adler\local\exceptions\not_an_adler_course_exception;
use moodle_exception;
use restore_controller;
use restore_controller_exception;
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

    /**
     * @throws invalid_parameter_exception
     * @throws not_an_adler_course_exception
     * @throws restore_controller_exception
     * @throws moodle_exception
     * @throws Exception
     */
    public static function execute($mbz): array {
        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), array(
            'mbz' => $mbz,
        ));
        $mbz = $params['mbz'];

        // Saving file (taken from externallib.php (file) upload)
        $dir = make_temp_directory('wsupload');

        $filename = uniqid('wsupload', true) . '_' . time() . '.tmp';

        if (file_exists($dir . $filename)) {
            $savedfilepath = $dir . uniqid('m') . $filename;
        } else {
            $savedfilepath = $dir . $filename;
        }

        file_put_contents($savedfilepath, base64_decode($mbz));


        // extract mbz and prepare restore
        $categoryid = 1; // e.g. 1 == Miscellaneous
        $userdoingrestore = 2; // e.g. 2 == admin
        $courseid = restore_dbops::create_new_course('', '', $categoryid);


        $foldername = restore_controller::get_tempdir_name($courseid, $userdoingrestore);
        $fp = get_file_packer('application/vnd.moodle.backup');
        $tempdir = make_backup_temp_directory($foldername);
        $fp->extract_to_pathname($savedfilepath, $tempdir);

        // validate course is adler course
        $course_xml_path = $tempdir . '/course/course.xml';
        $contents = file_get_contents($course_xml_path);
        if(!property_exists(simplexml_load_string($contents)->plugin_local_adler_course, 'adler_course')) {
            throw new not_an_adler_course_exception();
        }

        // do restore: create controller
        $controller = new restore_controller(
            $foldername,
            $courseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $userdoingrestore,
            backup::TARGET_NEW_COURSE
        );

        // do restore: set required enrolment setting
        $plan = $controller->get_plan();
        $plan->get_tasks()[0]->get_setting('enrolments')->set_value(backup::ENROL_ALWAYS);

        // do restore: execute
        $controller->execute_precheck();
        $controller->execute_plan();
        $controller->destroy();


        // delete file $savedfilepath
        unlink($savedfilepath);
        // delete tempdir
        fulldelete($tempdir);


        return array('data' => array(
            'course_id' => $courseid,
        ));
    }
}
