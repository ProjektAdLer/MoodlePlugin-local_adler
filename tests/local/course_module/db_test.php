<?php

namespace local_adler\local\course_module;


use dml_exception;
use local_adler\lib\local_adler_testcase;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


class db_test extends local_adler_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');
    }

    public function provide_test_get_adler_course_module_by_uuid_data() {
        return [
            'success' => [
                'success' => true,
            ],
            'exception' => [
                'success' => false,
            ]
        ];
    }

    /**
     * @dataProvider provide_test_get_adler_course_module_by_uuid_data
     */
    public function test_get_adler_course_module_by_uuid($success) {
        // create course
        $course = $this->getDataGenerator()->create_course();

        // create module
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        // create adler_section entry
        if ($success) {
            $adler_cm = $this->adler_generator->create_adler_course_module($module->cmid);
        } else {
            $adler_cm = $this->adler_generator->create_adler_course_module(123456789);
            $this->expectException(dml_exception::class);
        }

        // call function
        $result = db::get_adler_course_module_by_uuid($adler_cm->uuid, $course->id);

        // check result
        $this->assertEquals($adler_cm, $result);
    }
}
