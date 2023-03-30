<?php

namespace local_adler\local\section;


use completion_info;
use local_adler\adler_score_helpers;
use local_adler\lib\local_adler_testcase;
use local_adler\local\exceptions\not_an_adler_section_exception;
use ReflectionClass;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


class section_test extends local_adler_testcase {
    public function provide_test_construct_data() {
        return [
            'valid section' => [
                'section_response' => (object)[
                    'id' => 1,
                    'course_id' => 1,
                    'name' => 'Test Section',
                    'required_points_to_complete' => 100
                ],
                'exception' => null
            ],
            'invalid section' => [
                'section_response' => false,
                'exception' => not_an_adler_section_exception::class
            ]
        ];
    }

    /**
     * @dataProvider provide_test_construct_data
     */
    public function test_construct($section_response, $exception) {
        $return_map = [
            [db::class, 'get_adler_section', 1, $section_response]
        ];

        $section_mock = $this->getMockBuilder(section::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['callStatic'])
            ->getMock();
        $section_mock->method('callStatic')
            ->will($this->returnValueMap($return_map));

        if ($exception) {
            $this->expectException($exception);
        }

        $section_mock->__construct(1);

        // get section property
        if (!$exception) {
            $reflection = new ReflectionClass($section_mock);
            $property = $reflection->getProperty('section');
            $property->setAccessible(true);
            $this->assertEquals($section_response, $property->getValue($section_mock));
        }
    }

    public function provide_test_is_completed_data() {
        return [
            'completed' => [
                'modules' => [
                    1 => 50,
                    2 => 50,
                    3 => 50
                ],
                'expected' => true
            ],
            'not completed' => [
                'modules' => [
                    1 => 0,
                    2 => 0
                ],
                'expected' => false
            ],
            'edge case completed' => [
                'modules' => [
                    1 => 100
                ],
                'expected' => true
            ],
            'edge case not completed' => [
                'modules' => [
                    1 => 99.9
                ],
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider provide_test_is_completed_data
     */
    public function test_is_completed($modules, $expected) {
        // mock static function calls
        $return_map = [
            [db::class, 'get_course_modules_by_section_id', 1,
                array_map(function ($id) {
                    return (object)['id' => $id];
                }, array_keys($modules))
            ],
            [adler_score_helpers::class, 'get_achieved_scores', array_keys($modules), 1, array_values($modules)]
        ];

        $section_mock = $this->getMockBuilder(section::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['callStatic'])
            ->getMock();
        $section_mock->method('callStatic')
            ->will($this->returnValueMap($return_map));


        // set section and section_id properties
        $reflection = new ReflectionClass($section_mock);
        $property = $reflection->getProperty('section_id');
        $property->setAccessible(true);
        $property->setValue($section_mock, 1);

        $property = $reflection->getProperty('section');
        $property->setAccessible(true);
        $property->setValue($section_mock, (object)['required_points_to_complete' => 100]);


        // call function
        $this->assertEquals($expected, $section_mock->is_completed(1));
    }

    public function provide_test_is_completed_integration_data() {
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
     * @dataProvider provide_test_is_completed_integration_data
     */
    public function test_is_completed_integration($cm_score, $expected) {

        // create course and module
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'completion' => 1]);
        $section_id = get_fast_modinfo($course->id)->get_cm($module->cmid)->section;

        // create adler entries for course, section and module
        $adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');
        $adler_generator->create_adler_course_object($course->id);
        $adler_generator->create_adler_section_object($section_id);
        $adler_generator->create_adler_score_item($module->cmid, ['score_max' => $cm_score]);

        // create user
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // set module completed for user
        $completion = new completion_info($course);
        $cm_format2 = get_coursemodule_from_id(null, $module->cmid, 0, false, MUST_EXIST);
        $completion->update_state($cm_format2, COMPLETION_COMPLETE, $user->id);

        // test function
        $section = new section($section_id);
        $result = $section->is_completed($user->id);
        $this->assertEquals($expected, $result);
    }
}
