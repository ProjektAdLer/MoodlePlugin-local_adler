<?php

namespace local_adler\local\section;


use local_adler\lib\local_adler_testcase;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


class db_test extends local_adler_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');
    }

    public function test_get_adler_section() {
        // create adler_section entry
        $adler_section = $this->adler_generator->create_adler_section_object(1);

        $db_adler_section = db::get_adler_section(1);

        $this->assertEquals($adler_section, $db_adler_section);
    }

    public function test_get_course_modules_by_section_id() {
        // create course
        $course = $this->getDataGenerator()->create_course();

        // create course module
        $course_module1 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $course_module2 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        // get section id
        $section_id = get_fast_modinfo($course->id)->get_cm($course_module2->cmid)->section;

        // create some other course with module (should not be included in result)
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('assign', ['course' => $course2->id]);

        // call function
        $db_course_modules = db::get_course_modules_by_section_id($section_id);

        // check result
        $this->assertEquals([$course_module1->cmid, $course_module2->cmid], array_keys($db_course_modules));
    }
}
