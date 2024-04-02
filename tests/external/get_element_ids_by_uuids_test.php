<?php

namespace local_adler\external;


use context_course;
use context_module;
use dml_exception;
use invalid_parameter_exception;
use local_adler\lib\adler_externallib_testcase;
use Mockery;
use ReflectionClass;
use local_adler\local\section\db as section_db;
use local_adler\local\course_module\db as cm_db;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


/**
 * @runTestsInSeparateProcesses
 */
class get_element_ids_by_uuids_test extends adler_externallib_testcase {
    public function provide_test_execute_data() {
        return [
            'cm' => [
                'element' => [
                    [
                        'element_type' => 'cm',
                        'uuid' => '6464ecd9-6a88-4dd3-bc82-5e5c6b107075',
                        'course_id' => '46',
                    ]
                ],
                'adler_element_exists' => true,
                'expected_result' => [
                    [
                        "course_id" => "46",
                        "element_type" => "cm",
                        "uuid" => "6464ecd9-6a88-4dd3-bc82-5e5c6b107075",
                        "moodle_id" => 351,
                        "context_id" => 411
                    ]
                ],
                'expected_exception' => null,
            ],
            'section' => [
                'element' => [
                    [
                        'element_type' => 'section',
                        'uuid' => '6464ece5-2cf4-4cbc-9f08-3d106b107075',
                        'course_id' => '46',
                    ]
                ],
                'adler_element_exists' => true,
                'expected_result' => [
                    [
                        "course_id" => "46",
                        "element_type" => "section",
                        "uuid" => "6464ece5-2cf4-4cbc-9f08-3d106b107075",
                        "moodle_id" => 16,
                        "context_id" => null
                    ]
                ],
                'expected_exception' => null,
            ],
            'invalid element_type' => [
                'element' => [
                    [
                        'element_type' => 'invalid',
                        'uuid' => '6464ece5-2cf4-4cbc-9f08-3d106b107075',
                        'course_id' => '46',
                    ]
                ],
                'adler_element_exists' => true,
                'expected_result' => null,
                'expected_exception' => invalid_parameter_exception::class,
            ],
            'cm adler element does not exist' => [
                'element' => [
                    [
                        'element_type' => 'cm',
                        'uuid' => '6464ecd9-6a88-4dd3-bc82-5e5c6b107075',
                        'course_id' => '46',
                    ]
                ],
                'adler_element_exists' => false,
                'expected_result' => null,
                'expected_exception' => invalid_parameter_exception::class,
            ],
            'section adler element does not exist' => [
                'element' => [
                    [
                        'element_type' => 'section',
                        'uuid' => '6464ece5-2cf4-4cbc-9f08-3d106b107075',
                        'course_id' => '46',
                    ]
                ],
                'adler_element_exists' => false,
                'expected_result' => null,
                'expected_exception' => invalid_parameter_exception::class,
            ],
        ];
    }

    /**
     * @dataProvider provide_test_execute_data
     */
    public function test_execute($element, $adler_element_exists, $expected_result, $expected_exception) {
        $course = $this->getDataGenerator()->create_course();

        // mock section db
        $section_db_mock = Mockery::mock('overload:' . section_db::class);
        // mock cm_db
        $cm_db_mock = Mockery::mock('overload:' . cm_db::class);

        if ($element[0]['element_type'] == 'section') {
            if ($adler_element_exists) {
                $section_db_mock->shouldReceive('get_adler_section_by_uuid')->andReturn((object)['section_id' => 16]);
            } else {
                $section_db_mock->shouldReceive('get_adler_section_by_uuid')->andThrow(new dml_exception(''));
            }
            $section_db_mock->shouldReceive('get_adler_section_by_uuid')->andReturn((object)['section_id' => 16]);
            $section_db_mock->shouldReceive('get_moodle_section')->with(16)->andReturn((object)['course' => 42]);

            // mock context_course
            $context_course_mock = Mockery::mock(context_course::class);
            $context_course_mock->shouldReceive('instance')->andReturn('instance');

            $reflected_class = new ReflectionClass(get_element_ids_by_uuids::class);
            $property = $reflected_class->getProperty('context_course');
            $property->setAccessible(true);
            $property->setValue(null, $context_course_mock->mockery_getName());

            // mock validate_context
            $get_element_ids_by_uuids_mock = Mockery::mock(get_element_ids_by_uuids::class)->makePartial();
            $get_element_ids_by_uuids_mock->shouldReceive('validate_context')->andReturn(1);
        } else {
            if ($adler_element_exists) {
                $cm_db_mock->shouldReceive('get_adler_course_module_by_uuid')->andReturn((object)['cmid' => 351]);
            } else {
                $cm_db_mock->shouldReceive('get_adler_course_module_by_uuid')->andThrow(new dml_exception(''));
            }

            // mock context_module
            $context_module_mock = Mockery::mock(context_module::class);
            $context_module_mock->shouldReceive('instance')->andReturn((object)['id' => 411]);

            $reflected_class = new ReflectionClass(get_element_ids_by_uuids::class);
            $property = $reflected_class->getProperty('context_module');
            $property->setAccessible(true);
            $property->setValue(null, $context_module_mock->mockery_getName());

            // mock validate_context
            $get_element_ids_by_uuids_mock = Mockery::mock(get_element_ids_by_uuids::class)->makePartial();
            $get_element_ids_by_uuids_mock->shouldReceive('validate_context')->andReturn(2);
        }

        if ($expected_exception !== null) {
            $this->expectException($expected_exception);
        }

        $res = $get_element_ids_by_uuids_mock::execute($element);

        $this->assertEquals(['data' => $expected_result], $res);

    }


    public function provide_test_execute_returns_data() {
        return [
            [
                'data' => ['data' => [[
                    "course_id" => "46",
                    "element_type" => "section",
                    "uuid" => "6464ece5-2cf4-4cbc-9f08-3d106b107075",
                    "moodle_id" => 16,
                    "context_id" => null
                ]]],
                'success' => true
            ], [
                'data' => ['data' => [[
                    "course_id" => "46",
                    "element_type" => "cm",
                ]]],
                'success' => false
            ]
        ];
    }

    /**
     * @dataProvider provide_test_execute_returns_data
     */
    public function test_execute_returns($data, $success) {
        if (!$success) {
            $this->expectException(invalid_parameter_exception::class);
        }

        $result = get_element_ids_by_uuids::validate_parameters(get_element_ids_by_uuids::execute_returns(), $data);

        $this->assertEquals($data, $result);
    }
}