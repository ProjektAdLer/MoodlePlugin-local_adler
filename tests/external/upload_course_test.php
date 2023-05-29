<?php

namespace local_adler\external;


use backup;
use backup_controller;
use invalid_parameter_exception;
use local_adler\lib\local_adler_externallib_testcase;
use local_adler\local\exceptions\not_an_adler_course_exception;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


/**
 * @runTestsInSeparateProcesses
 */
class upload_course_test extends local_adler_externallib_testcase {
    public function generate_mbz(bool $is_adler_course): string {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // generate course
        $course = $this->getDataGenerator()->create_course();
        // adler course
        if ($is_adler_course) {
            $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object($course->id);
        }

        // create backup (mbz)
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();
        $bc->destroy();

        $file = $bc->get_results();
        $file = reset($file);
        $filepath = $file->get_contenthash();
        $filepath = $CFG->dataroot . '/filedir/' . substr($filepath, 0, 2) . '/' . substr($filepath, 2, 2) . '/' . $filepath;


        return $filepath;
    }

    public function provide_test_execute_data() {
        return [
            'success' => [
                'is_adler_course' => true,
                'upload_error' => UPLOAD_ERR_OK,
                'fail_validation' => false,
            ],
            'fail_validation' => [
                'is_adler_course' => true,
                'upload_error' => UPLOAD_ERR_OK,
                'fail_validation' => true,
            ],
            'mbz_upload_failed' => [
                'is_adler_course' => true,
                'upload_error' => UPLOAD_ERR_NO_FILE,
                'fail_validation' => false,
            ],
            'not_adler_course' => [
                'is_adler_course' => false,
                'upload_error' => UPLOAD_ERR_OK,
                'fail_validation' => false,
            ],
        ];
    }

    /**
     * @dataProvider provide_test_execute_data
     */
    public function test_execute($is_adler_course, $upload_error, $fail_validation) {
        $test_course_filepath = $this->generate_mbz($is_adler_course);

        global $DB;
        $course_count_before = $DB->count_records('course');

        if (!$is_adler_course) {
            $this->expectException(not_an_adler_course_exception::class);
        }

        if ($upload_error !== UPLOAD_ERR_OK) {
            $this->expectException(invalid_parameter_exception::class);
        }

        if ($fail_validation) {
            $this->expectException(invalid_parameter_exception::class);
        } else {
            $_FILES['mbz'] = [
                'name' => 'test.mbz',
                'type' => 'application/zip',
                'tmp_name' => $test_course_filepath,
                'error' => $upload_error,
                'size' => 123,
            ];
        }

        upload_course::execute();

        $course_count_after = $DB->count_records('course');

        $this->assertEquals($course_count_before + 1, $course_count_after);
    }


    public function provide_test_execute_returns_data() {
        return [
            'success' => [
                'success' => true,
            ],
            'fail' => [
                'success' => false,
            ],
        ];
    }

    /**
     * @dataProvider provide_test_execute_returns_data
     */
    public function test_execute_returns($success) {
        if (!$success) {
            $this->expectException(invalid_parameter_exception::class);
            upload_course::validate_parameters(upload_course::execute_returns(), 'blub');
        } else {
            upload_course::validate_parameters(upload_course::execute_returns(), ['data' => ['course_id' => 1]]);
        }
    }

    public function test_execute_parameters() {
        upload_course::validate_parameters(upload_course::execute_parameters(), []);
    }
}