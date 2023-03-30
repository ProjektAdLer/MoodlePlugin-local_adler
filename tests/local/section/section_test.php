<?php

namespace local_adler\local\section;


use local_adler\lib\local_adler_testcase;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

//require_once($CFG->dirroot . '/local/adler/tests/mocks.php');


class section_test extends local_adler_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');
    }

    public function provide_test_is_completed_data() {
        return [
            'completed' => [
                'cm_score' => 100,
                'expected' => true
            ],
            'not completed' => [
                'cm_score' => 0,
                'expected' => false
            ]
        ];
    }

    /**
     * @dataProvider provide_test_is_completed_data
     */
    public function test_is_completed($cm_score, $expected) {
        global $DB;

        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'completion' => 1]);


        $section_id = get_fast_modinfo($course->id)->get_cm($module->cmid)->section;
//        $section = $DB->get_record('course_sections', ['id' => $section_id]);


        $this->adler_generator->create_adler_course_object($course->id);
        $adler_section = $this->adler_generator->create_adler_section_object($section_id);
        $adler_score = $this->adler_generator->create_adler_score_item($module->cmid, ['score_max' => $cm_score]);


        // create user
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // set module completed for user
        $completion = new \completion_info($course);
        $fucking_anderes_course_module_format = get_coursemodule_from_id(null, $module->cmid, 0, false, MUST_EXIST);
        $completion->update_state($fucking_anderes_course_module_format, COMPLETION_COMPLETE, $user->id);


        $section = new section($section_id);
        $result = $section->is_completed($user->id);
        $this->assertEquals($expected, $result);
    }
}
