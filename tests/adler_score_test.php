<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler;


use cm_info;
use completion_info;
use core\di;
use grade_item;
use local_adler\lib\adler_testcase;
use local_adler\local\db\adler_course_module_repository;
use local_adler\local\exceptions\user_not_enrolled_exception;
use Mockery;
use moodle_exception;
use ReflectionClass;
use stdClass;
use Throwable;
use TypeError;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');
require_once($CFG->libdir . '/completionlib.php');  # sometimes randomly required

class adler_score_test extends adler_testcase {
    private stdClass $course;
    private stdClass $module;
    private stdClass $user;
    private cm_info $url_module_cm_info;

    public function setUp(): void {
        parent::setUp();

        // create user
        $this->user = $this->getDataGenerator()->create_user();

        // create course
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        // create module
        $this->module = $this->getDataGenerator()->create_module('url', [
            'course' => $this->course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completeionview' => 1,
            'completionpassgrade' => 0
        ]);
    }


    public function provide_test_construct_data() {
        // double array for each case because phpunit otherwise splits the object into individual params
        return [
            'default case' => [[
                'enrolled' => true,
                'user_param' => null,
                'set_user_object' => true,
                'course_module_param' => 'correct',
                'is_adler_course' => true,
                'is_adler_cm' => true,
                'expect_exception' => false,
                'expect_exception_message' => null,
            ]],
            'with user id param' => [[
                'enrolled' => true,
                'user_param' => 'id',
                'set_user_object' => true,
                'course_module_param' => 'correct',
                'is_adler_course' => true,
                'is_adler_cm' => true,
                'expect_exception' => false,
                'expect_exception_message' => null,
            ]],
            'not enrolled' => [[
                'enrolled' => false,
                'user_param' => null,
                'set_user_object' => true,
                'course_module_param' => 'correct',
                'is_adler_course' => true,
                'is_adler_cm' => true,
                'expect_exception' => user_not_enrolled_exception::class,
                'expect_exception_message' => "user_not_enrolled",
            ]],
            'invalid module format' => [[
                'enrolled' => true,
                'user_param' => null,
                'set_user_object' => true,
                'course_module_param' => 'incorrect',
                'is_adler_course' => true,
                'is_adler_cm' => true,
                'expect_exception' => TypeError::class,
                'expect_exception_message' => 'must be of type cm_info',
            ]],
            'not adler course' => [[
                'enrolled' => true,
                'user_param' => null,
                'set_user_object' => true,
                'course_module_param' => 'correct',
                'is_adler_course' => false,
                'is_adler_cm' => true,
                'expect_exception' => moodle_exception::class,
                'expect_exception_message' => 'not_an_adler_course',
            ]],
        ];
    }

    /**
     * @dataProvider provide_test_construct_data
     * @runInSeparateProcess
     *
     * # ANF-ID: [MVP12, MVP10, MVP9, MVP8, MVP7]
     */
    public function test_construct($test) {
        // Create mock for class adler_course_module_repository using Mockery
        $adler_course_module_repository = Mockery::mock(adler_course_module_repository::class);

        // inject the mock into the container
        di::set(adler_course_module_repository::class, $adler_course_module_repository);

        // Create mock for helpers using Mockery
        $helpers_mock = Mockery::mock('alias:' . helpers::class);

        $module_format_correct = get_fast_modinfo($this->course->id)->get_cm($this->module->cmid);

        if ($test['enrolled']) {
            $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);
        }

        $helpers_mock->shouldReceive('course_is_adler_course')->andReturn($test['is_adler_course']);

        if ($test['is_adler_cm']) {
            $adler_course_module_repository->shouldReceive('get_adler_course_module_by_cmid')->andReturn((object)['id' => 1, 'moduleid' => $module_format_correct->id, 'score' => 17]);
        } else {
            $adler_course_module_repository->shouldReceive('get_adler_course_module_by_cmid')->andThrow(new moodle_exception('not_an_adler_cm', 'test'));
        }

        if ($test['set_user_object']) {
            $this->setUser($this->user);
        }

        if ($test['course_module_param'] === 'correct') {
            $test['course_module_param'] = $module_format_correct;
        } else if ($test['course_module_param'] === 'incorrect') {
            $test['course_module_param'] = $this->module;
        }

        if ($test['user_param'] === 'id') {
            $test['user_param'] = (int) $this->user->id;
        }

        // call method
        try {
            $result = new adler_score($test['course_module_param'], $test['user_param']);
        } catch (Throwable $e) {
            if ($test['expect_exception'] === false) {
                $this->fail('Unexpected exception: ' . get_class($e) . ' - ' . $e->getMessage());
            }
            $this->assertStringContainsString($test['expect_exception'], get_class($e));
            if ($test['expect_exception_message'] !== null) {
                $this->assertStringContainsString($test['expect_exception_message'], $e->getMessage());
            }
            return;
        }
        if ($test['expect_exception'] !== false) {
            $this->fail('Exception expected');
        }

        // No exception thrown and no exception expected -> check result
        // Reflect the adler_score object
        $reflection = new ReflectionClass(adler_score::class);

        // Make the score_item attribute accessible
        $scoreItemProperty = $reflection->getProperty('score_item');
        $scoreItemProperty->setAccessible(true);

        // test score
        $this->assertEquals(17, $scoreItemProperty->getValue($result)->score);
    }

    public function provide_test_get_primitive_score_data() {
        return [
            'complete' => [
                'completion_enabled_cm' => true,
                'completion_enabled_course' => true,
                'completion_state' => COMPLETION_COMPLETE,
                'expect_exception' => false,
                'expect_exception_message' => null,
                'expect_score' => 100,
            ],
            'incomplete' => [
                'completion_enabled_cm' => true,
                'completion_enabled_course' => true,
                'completion_state' => COMPLETION_INCOMPLETE,
                'expect_exception' => false,
                'expect_exception_message' => null,
                'expect_score' => 0,
            ],
            'completion_disabled' => [
                'completion_enabled_cm' => false,
                'completion_enabled_course' => false,
                'completion_state' => COMPLETION_INCOMPLETE,
                'expect_exception' => moodle_exception::class,
                'expect_exception_message' => "completion_not_enabled",
                'expect_score' => 0,
            ],
            'completion_disabled_cm' => [
                'completion_enabled_cm' => false,
                'completion_enabled_course' => true,
                'completion_state' => COMPLETION_INCOMPLETE,
                'expect_exception' => moodle_exception::class,
                'expect_exception_message' => "completion_not_enabled",
                'expect_score' => 0,
            ],
        ];
    }

    private function set_up_course_with_primitive_element(bool $enable_completion_course, bool $enable_completion_module) {
        // create user, course and enrol user
        $this->user = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => $enable_completion_course ? '1' : '0']);
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);
        $this->setUser($this->user);

        // create primitive activity
        $cm_data = [
            'course' => $this->course->id,
            'completion' => $enable_completion_module ? COMPLETION_TRACKING_AUTOMATIC : COMPLETION_TRACKING_NONE];
        if ($enable_completion_module) {
            $cm_data += [
                'completionview' => 1,
                'completionpassgrade' => 0
            ];
        }
        $url_module = $this->getDataGenerator()->get_plugin_generator('mod_url')->create_instance($cm_data);
        $this->url_module_cm_info = get_fast_modinfo($url_module->course)->get_cm($url_module->cmid);

        // make course and module adler course/module
        $adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');
        $adler_generator->create_adler_course_object($this->course->id);
        $adler_generator->create_adler_course_module($url_module->cmid);
    }

    /**
     *
     * @dataProvider provide_test_get_primitive_score_data
     * # ANF-ID: [MVP7, MVP9, MVP10, MVP8]
     */
    public function test_get_primitive_score(bool $completion_enabled_cm, bool $completion_enabled_course, int $completion_state, string|false $expect_exception, ?string $expect_exception_message, int $expect_score) {
        $this->set_up_course_with_primitive_element($completion_enabled_course, $completion_enabled_cm);

        if ($completion_state === COMPLETION_COMPLETE) {
            $completion = new completion_info($this->course);
            $completion->set_module_viewed($this->url_module_cm_info);
        }

        $adler_score = new adler_score($this->url_module_cm_info);
        try {
            $result = $adler_score->get_score_by_completion_state();
        } catch (Throwable $e) {
            $this->assertStringContainsString($expect_exception, get_class($e));
            if ($expect_exception_message !== null) {
                $this->assertStringContainsString($expect_exception_message, $e->getMessage());
            }
            return;
        }

        $this->assertEquals($expect_score, $result);
    }

    private function set_up_course_with_h5p_grade_element() {
        // create user, course and enrol user
        $this->user = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);
        $this->setUser($this->user);

        // create h5p with completionpassgrade
        $this->h5p_module = $this->getDataGenerator()->create_module('h5pactivity', [
            'course' => $this->course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 0,
            'completionpassgrade' => 1,
            'completiongradeitemnumber' => 0
        ]);

        // make course and module adler course/module
        $adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');
        $adler_generator->create_adler_course_object($this->course->id);
        $this->h5p_adler_cm = $adler_generator->create_adler_course_module($this->h5p_module->cmid);
    }

    /**
     * # ANF-ID: [MVP7, MVP8, MVP9]
     */
    public function test_get_score_for_h5p_grade_learning_element() {
        $this->set_up_course_with_h5p_grade_element();

        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $grade_item = grade_item::fetch([
            'itemname' => $this->h5p_module->name,
            'gradetype' => GRADE_TYPE_VALUE,
            'courseid' => $this->h5p_module->course
        ]);
        $grade_data_class = new stdClass();
        $grade_data_class->userid = $this->user->id;
        $grade_data_class->rawgrade = $grade_item->grademax;
        require_once($CFG->dirroot . '/mod/h5pactivity/lib.php');
        h5pactivity_grade_item_update($this->h5p_module, $grade_data_class);

        $h5p_module_as_course_modinfo = get_fast_modinfo($this->course->id)->get_cm($this->h5p_module->cmid);
        $adler_score = new adler_score($h5p_module_as_course_modinfo);

        $this->assertEquals($this->h5p_adler_cm->score_max, $adler_score->get_score_by_completion_state());
    }
}
