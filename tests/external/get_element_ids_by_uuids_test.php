<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler\external;


use core\di;
use dml_exception;
use invalid_parameter_exception;
use local_adler\lib\adler_externallib_testcase;
use local_adler\local\db\adler_course_module_repository;
use local_adler\local\db\adler_sections_repository;
use local_adler\moodle_core;
use local_logging\logger;
use Mockery;
use ReflectionClass;
use require_login_exception;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


class get_element_ids_by_uuids_test extends adler_externallib_testcase {
    public function provide_test_execute_data() {
        return [
            'user enrolled in course' => [
                'user_enrolled' => true
            ],
            'user not enrolled in course' => [
                'user_enrolled' => false
            ]
        ];
    }

    /**
     * @dataProvider provide_test_execute_data
     */
    public function test_execute(bool $user_enrolled) {
        $adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');

        // create course
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $adler_generator->create_adler_course_object($course->id);

        // create and enroll user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        if ($user_enrolled) {
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        // create resource module
        $resource_module = $this->getDataGenerator()->create_module('resource', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
            'completionpassgrade' => 0
        ]);
        $resource_adler_cm = $adler_generator->create_adler_course_module($resource_module->cmid);

        // mock get_element_ids_by_uuids
        $get_element_ids_by_uuids_mock = Mockery::mock(get_element_ids_by_uuids::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $get_element_ids_by_uuids_mock->shouldReceive('get_moodle_and_context_id')->andReturn([32,33]);

        if (!$user_enrolled) {
            $this->expectException(require_login_exception::class);
        }

        $result = $get_element_ids_by_uuids_mock::execute([
            [
                'course_id' => $course->id,
                'element_type' => 'cm',
                'uuid' => $resource_adler_cm->uuid
            ]
        ]);

        $this->assertEquals(['data' => [
            [
                "course_id" => $course->id,
                "element_type" => "cm",
                "uuid" => $resource_adler_cm->uuid,
                "moodle_id" => 32,
                "context_id" => 33
            ]
        ]], $result);
    }

    public function provide_test_get_moodle_and_context_id_data(): array {
        return [
            'test_section_element' => [
                'element_type' => 'section',
                'uuid' => 'section-uuid',
                'course_id' => 1,
                'expected_moodle_id' => 101,
                'expected_context_id' => null,
                'repository_method' => 'get_adler_section_by_uuid',
                'repository_class' => adler_sections_repository::class,
                'repository_return' => (object)['section_id' => 101],
                'expect_exception' => false,
            ],
            'test_course_module_element' => [
                'element_type' => 'cm',
                'uuid' => 'cm-uuid',
                'course_id' => 1,
                'expected_moodle_id' => 202,
                'expected_context_id' => 303,
                'repository_method' => 'get_adler_course_module',
                'repository_class' => adler_course_module_repository::class,
                'repository_return' => (object)['cmid' => 202],
                'expect_exception' => false,
            ],
            'test_invalid_element_type' => [
                'element_type' => 'invalid',
                'uuid' => 'invalid-uuid',
                'course_id' => 1,
                'expected_moodle_id' => null,
                'expected_context_id' => null,
                'repository_method' => null,
                'repository_class' => null,
                'repository_return' => null,
                'expect_exception' => true,
            ],
            'test_section_not_found_exception' => [
                'element_type' => 'section',
                'uuid' => 'invalid-section-uuid',
                'course_id' => 1,
                'expected_moodle_id' => null,
                'expected_context_id' => null,
                'repository_method' => 'get_adler_section_by_uuid',
                'repository_class' => adler_sections_repository::class,
                'repository_return' => null,
                'expect_exception' => true,
            ],
            'test_course_module_not_found_exception' => [
                'element_type' => 'cm',
                'uuid' => 'invalid-cm-uuid',
                'course_id' => 1,
                'expected_moodle_id' => null,
                'expected_context_id' => null,
                'repository_method' => 'get_adler_course_module',
                'repository_class' => adler_course_module_repository::class,
                'repository_return' => null,
                'expect_exception' => true,
            ],
        ];
    }

    /**
     * @dataProvider provide_test_get_moodle_and_context_id_data
     */
    public function test_get_moodle_and_context_id(
        string $element_type,
        string $uuid,
        int $course_id,
        ?int $expected_moodle_id,
        ?int $expected_context_id,
        ?string $repository_method,
        ?string $repository_class,
        ?object $repository_return,
        bool $expect_exception
    ): void {
        $logger = Mockery::mock(logger::class)->shouldIgnoreMissing();

        if ($repository_class && $repository_method) {
            $repository_mock = Mockery::mock($repository_class);
            if ($expect_exception) {
                $repository_mock->shouldReceive($repository_method)
                    ->with($uuid, $course_id)
                    ->andThrow(new dml_exception(''));
            } else {
                $repository_mock->shouldReceive($repository_method)
                    ->with($uuid, $course_id)
                    ->andReturn($repository_return);
            }
            di::set($repository_class, $repository_mock);
        }

        if ($element_type === 'cm' && !$expect_exception) {
            $moodle_core_mock = Mockery::mock(moodle_core::class);
            $moodle_core_mock->shouldReceive('context_module_instance')
                ->with($expected_moodle_id)
                ->andReturn((object)['id' => $expected_context_id]);
            di::set(moodle_core::class, $moodle_core_mock);
        }

        if ($expect_exception) {
            $this->expectException(invalid_parameter_exception::class);
        }

        $reflected_class = new ReflectionClass(get_element_ids_by_uuids::class);
        $method = $reflected_class->getMethod('get_moodle_and_context_id');
        $method->setAccessible(true);
        // call method
        list($moodle_id, $context_id) = $method->invoke(null, $element_type, $uuid, $course_id, $logger);

        if (!$expect_exception) {
            $this->assertEquals($expected_moodle_id, $moodle_id);
            $this->assertEquals($expected_context_id, $context_id);
        }
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
     *
     * # ANF-ID: [MVP6]
     */
    public function test_execute_returns($data, $success) {
        if (!$success) {
            $this->expectException(invalid_parameter_exception::class);
        }

        $result = get_element_ids_by_uuids::validate_parameters(get_element_ids_by_uuids::execute_returns(), $data);

        $this->assertEquals($data, $result);
    }

    public function test_execute_integrationtest() {
        // Create a user and a course
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create adler adaptivity section and course module
        $section_uuid = 'section-uuid-123';
        $cm_uuid = 'cm-uuid-456';
        // create url module
        $url_cm = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);
        $url_adler_cm = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($url_cm->cmid, ['uuid' => $cm_uuid]);
        // create adler section
        $section_id = get_fast_modinfo($course->id)->get_cm($url_cm->cmid)->section;
        $adler_section = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_section($section_id, ['uuid' => $section_uuid]);

        // Login as the user
        $this->setUser($user);

        // Prepare the input data
        $elements = [
            [
                'course_id' => $course->id,
                'element_type' => 'section',
                'uuid' => $section_uuid,
            ],
            [
                'course_id' => $course->id,
                'element_type' => 'cm',
                'uuid' => $cm_uuid,
            ],
        ];

        // Call the execute method
        $result = get_element_ids_by_uuids::execute($elements);

        // Check the result
        $this->assertCount(2, $result['data']);
        $this->assertEquals($section_id, $result['data'][0]['moodle_id']);
        $this->assertEquals($url_cm->cmid, $result['data'][1]['moodle_id']);
    }
}