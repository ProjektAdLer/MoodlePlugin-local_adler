<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler\external;


use context_module;
use core\di;
use local_adler\adler_score_helpers;
use local_adler\lib\adler_externallib_testcase;
use local_adler\moodle_core;
use Mockery;
use moodle_database;
use ReflectionClass;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


class score_get_course_scores_test extends adler_externallib_testcase {
    public static function provide_test_execute_data() {
        return [
            'success' => [
                'element_count' => 3,
            ],
            'require_login_exception' => [
                'element_count' => 0,
            ],
        ];
    }

    /**
     * @dataProvider provide_test_execute_data
     *
     * # ANF-ID: [MVP7]
     */
    public function test_execute($element_count) {
        $course = $this->getDataGenerator()->create_course();


        // mock context
        $context_mock = Mockery::mock(context_module::class);
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock->shouldReceive('context_course_instance')->andReturn($context_mock);

        // mock validate_context
        $score_get_course_scores_mock = Mockery::mock(score_get_course_scores::class)->makePartial();
        $score_get_course_scores_mock->shouldReceive('validate_context')->andReturn(true);


        // cant mock get_fast_modinfo, so create course with modules & generate get_achieved_scores return value and expected result
        $adler_score_helpers_mock_get_achieved_scores_return = [];
        $expected_result = [];
        for ($i = 0; $i < $element_count; $i++) {
            $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
            $adler_score_helpers_mock_get_achieved_scores_return[$module->id] = Mockery::mock('adler_score', [
                'get_cmid' => $module->id,
                'get_completion_state' => true,
                'get_score_by_completion_state' => $i * 2.0
            ]);
            $expected_result[] = [
                'module_id' => $module->id,
                'score' => $i * 2.0,
                'completed' => true
            ];
        }
        // adler score mock
        $adler_score_helpers_mock = Mockery::mock(adler_score_helpers::class);
        $adler_score_helpers_mock->shouldReceive('get_adler_score_objects')
            ->andReturn($adler_score_helpers_mock_get_achieved_scores_return);
        di::set(adler_score_helpers::class, $adler_score_helpers_mock);


        $result = $score_get_course_scores_mock->execute($course->id);

        // validate return value
        $this->assertEqualsCanonicalizing($expected_result, $result['data']);
    }

    public function test_execute_integration() {
        // upload user
        $upload_user = $this->getDataGenerator()->create_user();
        $this->setUser($upload_user);

        //// course
        $course = $this->getDataGenerator()->create_course(
            ['numsections' => 2, 'enablecompletion' => 1], // numsections starts at 0. so 0 means 1, 1 means 2, ...
            ['createsections' => true]
        );
        $adler_course = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object($course->id);
        $sections = di::get(moodle_database::class)->get_records('course_sections', ['course' => $course->id]);
        $sectionids = array_keys($sections);

        // Erste Section: "Section 0" Mit Kursinfo
        $cm_1_1 = $this->getDataGenerator()->create_module('label', ['course' => $course->id, 'section' => $sectionids[0]]);

        // Zweite Section: Zwei Resources
        $adler_section2 = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_section($sectionids[1], ['required_points_to_complete' => 2]);

        $cm_2_1 = $this->getDataGenerator()->create_module('resource', [
            'course' => $course->id,
            'section' => $sectionids[1],
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
            'completionpassgrade' => 0
        ]);
        $cm_2_2 = $this->getDataGenerator()->create_module('resource', [
            'course' => $course->id,
            'section' => $sectionids[1],
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
            'completionpassgrade' => 0
        ]);
        $cm_2_3 = $this->getDataGenerator()->create_module('resource', [
            'course' => $course->id,
            'section' => $sectionids[1],
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
            'completionpassgrade' => 0
        ]);
        $adler_cm_2_1 = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($cm_2_1->cmid, ['score_max' => 1]);
        $adler_cm_2_2 = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($cm_2_2->cmid, ['score_max' => 1]);
        $adler_cm_2_3 = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($cm_2_3->cmid, ['score_max' => 0]);  // optional resource

        // Dritte Section: externer Lerninhalt
        $cm_3_1 = $this->getDataGenerator()->create_module('resource', [
            'course' => $course->id,
            'section' => $sectionids[2],
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
            'completionpassgrade' => 0
        ]);


        //// user
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user);


        //// attempt one resource
        trigger_event_cm_viewed::execute((int)$cm_2_1->cmid);
        trigger_event_cm_viewed::execute((int)$cm_2_3->cmid);

        //// test
        $result = score_get_course_scores::execute($course->id);

        $this->assertEqualsCanonicalizing([
            ['module_id' => $cm_1_1->cmid],
            ['module_id' => $cm_2_1->cmid, 'score' => 1, 'completed' => true],
            ['module_id' => $cm_2_2->cmid, 'score' => 0, 'completed' => false],
            ['module_id' => $cm_2_3->cmid, 'score' => 0, 'completed' => true],
            ['module_id' => $cm_3_1->cmid],
        ], $result['data']);
    }

    /**
     * # ANF-ID: [MVP7]
     */
    public function test_execute_returns() {
        // this function just returns what get_adler_score_response_multiple_structure returns
        require_once(__DIR__ . '/lib_test.php');
        (new lib_test(''))->test_get_adler_score_response_multiple_structure(score_get_course_scores::class);
    }
}