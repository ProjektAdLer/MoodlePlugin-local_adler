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


        // base64 encode file
        $file = base64_encode(file_get_contents($filepath));

        return $file;
    }

    public function provide_test_execute_data() {
        return [
            'success' => [
                'is_adler_course' => true,
            ],
            'require_login_exception' => [
                'is_adler_course' => false,
            ],
        ];
    }

    /**
     * @dataProvider provide_test_execute_data
     */
    public function test_execute($is_adler_course) {
        $base64_encoded_course = $this->generate_mbz($is_adler_course);

        global $DB;
        $course_count_before = $DB->count_records('course');

        if (!$is_adler_course) {
            $this->expectException(not_an_adler_course_exception::class);
        }

        upload_course::execute($base64_encoded_course);

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
}