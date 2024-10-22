<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler\local\section;


use core\di;
use dml_exception;
use local_adler\local\db\adler_sections_repository;
use Mockery;
use completion_info;
use local_adler\adler_score_helpers;
use local_adler\lib\adler_testcase;
use local_adler\local\db\moodle_core_repository;
use local_adler\local\exceptions\not_an_adler_section_exception;
use ReflectionClass;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


class section_test extends adler_testcase {
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
                'section_response' => new dml_exception('error', 'Error message'),
                'exception' => not_an_adler_section_exception::class
            ]
        ];
    }

    /**
     * @dataProvider provide_test_construct_data
     *
     * # ANF-ID: [MVP12]
     */
    public function test_construct($section_response, $exception) {
        // Mock the adler_sections_repository
        $adler_sections_repository = Mockery::mock(adler_sections_repository::class);
        if ($section_response instanceof dml_exception) {
            $adler_sections_repository->shouldReceive('get_adler_section')->andThrow($section_response);
        } else {
            $adler_sections_repository->shouldReceive('get_adler_section')->andReturn($section_response);
        }
        di::set(adler_sections_repository::class, $adler_sections_repository);

        if ($exception) {
            $this->expectException($exception);
        }

        // Create the section object
        $section = new section(1);

        // Get section property
        if (!$exception) {
            $reflection = new ReflectionClass($section);
            $property = $reflection->getProperty('section');
            $property->setAccessible(true);
            $this->assertEquals($section_response, $property->getValue($section));
        }
    }

    public function provide_test_is_completed_data() {
        return [
            'completed' => [
                'modules_list' => [1, 2, 3],
                'scores_list' => [50,50,50],
                'expected' => true
            ],
            'not completed' => [
                'modules_list' => [1, 2],
                'scores_list' => [0, 0],
                'expected' => false
            ],
            'edge case completed' => [
                'modules_list' => [1],
                'scores_list' => [100],
                'expected' => true
            ],
            'edge case not completed' => [
                'modules_list' => [1],
                'scores_list' => [99.9],
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider provide_test_is_completed_data
     *
     * # ANF-ID: [MVP12]
     */
    public function test_is_completed($modules_list, $scores_list, $expected) {
        $section_id = 1;
        $user_id = 1;

        // Mock the adler_sections_repository
        $adler_sections_repository = $this->createMock(adler_sections_repository::class);
        $adler_sections_repository->method('get_adler_section')->willReturn((object)[
            'section_id' => $section_id,
            'required_points_to_complete' => 100
        ]);
        di::set(adler_sections_repository::class, $adler_sections_repository);

        // Mock the moodle_core_repository
        $moodle_core_repository = $this->createMock(moodle_core_repository::class);
        $moodle_core_repository->method('get_course_modules_by_section_id')->willReturn(array_map(function($id) {
            return (object)['id' => $id];
        }, $modules_list));
        di::set(moodle_core_repository::class, $moodle_core_repository);

        // Mock the adler_score_helpers
        $adler_score_helpers_mock = Mockery::mock(adler_score_helpers::class);
        $adler_score_helpers_mock
            ->shouldReceive('get_achieved_scores')
            ->with($modules_list, $user_id)
            ->andReturn($scores_list);
        di::set(adler_score_helpers::class, $adler_score_helpers_mock);

        // Create the section object
        $section = new section($section_id);

        // Call the method and check the result
        $this->assertEquals($expected, $section->is_completed($user_id));
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
     *
     * # ANF-ID: [MVP12]
     */
    public function test_is_completed_integration($cm_score, $expected) {

        // create course and module
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'completion' => 1]);
        $section_id = get_fast_modinfo($course->id)->get_cm($module->cmid)->section;

        // create adler entries for course, section and module
        $adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');
        $adler_generator->create_adler_course_object($course->id);
        $adler_generator->create_adler_section($section_id);
        $adler_generator->create_adler_course_module($module->cmid, ['score_max' => $cm_score]);

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
