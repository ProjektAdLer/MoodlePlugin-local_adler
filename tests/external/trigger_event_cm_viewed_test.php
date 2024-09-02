<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler\external;

defined('MOODLE_INTERNAL') || die();


use core_completion\cm_completion_details;
use local_adler\lib\adler_externallib_testcase;
use local_adler\local\exceptions\not_an_adler_cm_exception;
use local_adler\local\exceptions\not_an_adler_course_exception;


global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

require_once($CFG->dirroot . '/webservice/tests/helpers.php');


/**
 * @runTestsInSeparateProcesses
 */
class trigger_event_cm_viewed_test extends adler_externallib_testcase {
    public function setUp(): void {
        parent::setUp();

        // create user, course and enrol user
        $this->user = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);
        $this->setUser($this->user);

        // create resource module
        $this->resource_module = $this->getDataGenerator()->create_module('resource', [
            'course' => $this->course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
            'completionpassgrade' => 0
        ]);

        // create h5p with completionpassgrade
        $this->h5p_module = $this->getDataGenerator()->create_module('h5pactivity', [
            'course' => $this->course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 0,
            'completionpassgrade' => 1,
            'completiongradeitemnumber' => 0
        ]);
    }

    public function test_not_adler_course() {
        $this->expectException(not_an_adler_course_exception::class);

        // call execute
        trigger_event_cm_viewed::execute($this->resource_module->cmid, true);
    }

    public function test_not_adler_cm() {
        $this->expectException(not_an_adler_cm_exception::class);

        // make course adler course
        $this
            ->getDataGenerator()
            ->get_plugin_generator('local_adler')
            ->create_adler_course_object($this->course->id);

        // call execute
        trigger_event_cm_viewed::execute($this->resource_module->cmid, true);
    }

    public function test_execute_integration() {
        // make course adler course
        $this
            ->getDataGenerator()
            ->get_plugin_generator('local_adler')
            ->create_adler_course_object($this->course->id);

        // make both modules adler modules
        $resource_adler_cm = $this
            ->getDataGenerator()
            ->get_plugin_generator('local_adler')
            ->create_adler_course_module($this->resource_module->cmid);
        $h5p_adler_cm = $this
            ->getDataGenerator()
            ->get_plugin_generator('local_adler')
            ->create_adler_course_module($this->h5p_module->cmid);

        // call execute for both modules
        $result_resource = trigger_event_cm_viewed::execute($this->resource_module->cmid, true);
        $result_h5p = trigger_event_cm_viewed::execute($this->h5p_module->cmid, true);

        // assert both modules are marked as viewed
        $resource_cm_info = get_fast_modinfo($this->course->id)->get_cm($this->resource_module->cmid);
        $resource_view_state = cm_completion_details::get_instance($resource_cm_info, $this->user->id);
        $this->assertEquals(1, $resource_view_state->get_details()['completionview']->status);
        $this->assertTrue($resource_view_state->is_overall_complete());

        $h5p_cm_info = get_fast_modinfo($this->course->id)->get_cm($this->h5p_module->cmid);
        $h5p_view_state = cm_completion_details::get_instance($h5p_cm_info, $this->user->id);
//        $this->assertEquals(1, $h5p_view_state->get_details()['completionview']->status);  // "viewed" is not tracked if it is not a completion criteria
        $this->assertFalse($h5p_view_state->is_overall_complete());

        // validate response structure via execute_returns
        trigger_event_cm_viewed::validate_parameters(trigger_event_cm_viewed::execute_returns(), $result_resource);
        trigger_event_cm_viewed::validate_parameters(trigger_event_cm_viewed::execute_returns(), $result_h5p);

        // validate responses
        $this->assertEquals($resource_adler_cm->score_max, $result_resource['data']['0']['score']);
        $this->assertEquals(0, $result_h5p['data']['0']['score']);
    }
}
