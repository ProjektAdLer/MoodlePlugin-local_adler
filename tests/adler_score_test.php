<?php

namespace local_adler;


use coding_exception;
use completion_info;
use local_adler\lib\local_adler_testcase;
use local_adler\lib\static_mock_utilities_trait;
use local_adler\local\exceptions\user_not_enrolled_exception;
use mod_h5pactivity\local\grader;
use moodle_exception;
use ReflectionClass;
use Throwable;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');
require_once($CFG->dirroot . '/local/adler/tests/mocks.php');
require_once($CFG->libdir . '/completionlib.php');  # sometimes randomly required


class completion_info_mock extends completion_info {
    use static_mock_utilities_trait;

    public function is_enabled($cm = null) {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }

    public function get_data($cm, $wholecourse = false, $userid = 0, $unused = null) {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }
}

class helpers_mock extends helpers {
    use static_mock_utilities_trait;

    public static function course_is_adler_course($course_id): bool {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }

    public static function get_course_from_course_id($course_id) {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }
}

class adler_score_mock extends adler_score {
    use static_mock_utilities_trait;

    protected static string $helpers = helpers_mock::class;

    protected static string $adler_score_helpers = adler_score_helpers_mock::class;

    public function test_get_score_item() {
        return $this->score_item;
    }
}


class adler_score_test extends local_adler_testcase {
    public function setUp(): void {
        parent::setUp();

        // create user
        $this->user = $this->getDataGenerator()->create_user();

        // create course
        $this->course = $this->getDataGenerator()->create_course();

        // create module
        $this->module = $this->getDataGenerator()->create_module('url', ['course' => $this->course->id, 'completion' => 1]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $reflection = new ReflectionClass(adler_score::class);
        $property = $reflection->getProperty('completion_info');
        $property->setAccessible(true);
        $property->setValue(completion_info::class);
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
                'expect_exception' => coding_exception::class,
                'expect_exception_message' => 'course_module_format_not_valid',
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
     */
    public function test_construct($test) {
        // reset
        helpers_mock::reset_data();
        adler_score_mock::reset_data();
        adler_score_helpers_mock::reset_data();
        adler_score_helpers_mock::set_enable_mock('get_adler_score_record');

        $module_format_correct = get_fast_modinfo($this->course->id)->get_cm($this->module->cmid);

        if ($test['enrolled']) {
            $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);
        }

        helpers_mock::set_returns('course_is_adler_course', [$test['is_adler_course']]);

        if ($test['is_adler_cm']) {
            adler_score_helpers_mock::set_returns('get_adler_score_record', [(object)['id' => 1, 'moduleid' => $module_format_correct->id, 'score' => 17]]);
        } else {
            adler_score_helpers_mock::set_returns('get_adler_score_record', [null]);
            adler_score_helpers_mock::set_exceptions('get_adler_score_record', [new moodle_exception('not_an_adler_cm', 'test')]);
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
            $test['user_param'] = $this->user->id;
        }


        // call method
        try {
            $result = new adler_score_mock($test['course_module_param'], $test['user_param']);
        } catch (Throwable $e) {
            $this->assertEquals($test['expect_exception'], get_class($e));
            if ($test['expect_exception_message'] !== null) {
                $this->assertStringContainsString($test['expect_exception_message'], $e->getMessage());
            }
            return;
        }
        if ($test['expect_exception'] !== false) {
            $this->fail('Exception expected');
        }

        // No exception thrown and no exception expected -> check result
        // test score
        $this->assertEquals(17, $result->test_get_score_item()->score);
        $this->assertEquals($module_format_correct->id, $result->get_cmid());
    }

    public function provide_test_get_primitive_score_data() {
        return [
            'complete' => [[
                'completion_enabled' => true,
                'completion_state' => COMPLETION_COMPLETE,
                'expect_exception' => false,
                'expect_exception_message' => null,
                'expect_score' => 1,
            ]],
            'incomplete' => [[
                'completion_enabled' => true,
                'completion_state' => COMPLETION_INCOMPLETE,
                'expect_exception' => false,
                'expect_exception_message' => null,
                'expect_score' => 0,
            ]],
            'completion_disabled' => [[
                'completion_enabled' => false,
                'completion_state' => COMPLETION_INCOMPLETE,
                'expect_exception' => moodle_exception::class,
                'expect_exception_message' => "completion_not_enabled",
                'expect_score' => 0,
            ]],
        ];
    }

    /**
     * @dataProvider provide_test_get_primitive_score_data
     */
    public function test_get_primitive_score($data) {
        // create primitive activity
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_url');
        $cm = $generator->create_instance(array(
            'course' => $this->course->id,
        ));
        $cm_other_format = get_fast_modinfo($this->course->id)->get_cm($cm->cmid);

        // Create score (adler) item.
        $score_item = $this->getDataGenerator()
            ->get_plugin_generator('local_adler')
            ->create_adler_score_item($cm_other_format->id, [], false);


        // create adler_score object and set private properties
        $reflection = new ReflectionClass(adler_score::class);
        // create adler_score without constructor
        $adler_score = $reflection->newInstanceWithoutConstructor();
        // set private properties of adler_score
        $property = $reflection->getProperty('score_item');
        $property->setAccessible(true);
        $property->setValue($adler_score, $score_item);
        $property = $reflection->getProperty('course_module');
        $property->setAccessible(true);
        $property->setValue($adler_score, $cm_other_format);
        $property = $reflection->getProperty('user_id');
        $property->setAccessible(true);
        $property->setValue($adler_score, $this->user->id);

        // set completion_info mock
        $property = $reflection->getProperty('completion_info');
        $property->setAccessible(true);
        $property->setValue($adler_score, completion_info_mock::class);

        // set parameters for completion_info mock
        completion_info_mock::reset_data();
        completion_info_mock::set_returns('is_enabled', [$data['completion_enabled']]);
        completion_info_mock::set_returns('get_data', [(object)['completionstate' => $data['completion_state']]]);


        // call method
        try {
            $result = $adler_score->get_score();
        } catch (Throwable $e) {
            $this->assertEquals($data['expect_exception'], get_class($e));
            if ($data['expect_exception_message'] !== null) {
                $this->assertStringContainsString($data['expect_exception_message'], $e->getMessage());
            }
            return;
        }

        $this->assertEquals($data['expect_score'] == 1 ? $score_item->score_max : 0, $result);

    }

    /** h5p attempt generator is not calculating the scaled attribute.
     * When accessing h5pactivity_attempts it's not using the rawscore field,
     * but instead calculates the scaled value (maxscore * scaled), making this field required for tests.
     * This method works around this issue by calculating the redundant "scaled" field for all existing attempts.
     *
     * Note that this method does not set/update gradebook entries.
     */
    private function fix_scaled_attribute_of_h5pactivity_attempts() {
        global $DB;

        $attempts = $DB->get_records('h5pactivity_attempts');
        foreach ($attempts as $attempt) {
            $attempt->scaled = $attempt->rawscore / $attempt->maxscore;
            $DB->update_record('h5pactivity_attempts', $attempt);
        }
    }

    /**
     * @medium
     */
    public function test_get_score_for_h5p_learning_element() {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');


        // set current user (required by h5p generator)
        $this->setUser($this->user);


        // create h5p activity
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_h5pactivity');
        $cm = $generator->create_instance(array(
            'course' => $this->course->id,
        ));
        $cm_other_format = get_fast_modinfo($this->course->id)->get_cm($cm->cmid);

        // Create score (adler) item.
        $score_item_h5p = $this->getDataGenerator()
            ->get_plugin_generator('local_adler')
            ->create_adler_score_item($cm_other_format->id, [], false);


        // create adler_score object and set private properties
        $reflection = new ReflectionClass(adler_score::class);
        // create adler_score without constructor
        $adler_score = $reflection->newInstanceWithoutConstructor();
        // set private properties of adler_score
        $property = $reflection->getProperty('score_item');
        $property->setAccessible(true);
        $property->setValue($adler_score, $score_item_h5p);
        $property = $reflection->getProperty('course_module');
        $property->setAccessible(true);
        $property->setValue($adler_score, $cm_other_format);
        $property = $reflection->getProperty('user_id');
        $property->setAccessible(true);
        $property->setValue($adler_score, $this->user->id);


        // test no attempt
        // call method
        $result = $adler_score->get_score();
        $this->assertEquals(0, $result);


        // Test with attempts.

        // create grader
        $grader = new grader($cm);

        // array with test data for attempts with different maxscores and rawscores
        $test_data = [
            ['maxscore' => 100, 'rawscore' => 0, 'expected_score' => 0],
            ['maxscore' => 100, 'rawscore' => 100, 'expected_score' => 100],
            ['maxscore' => 100, 'rawscore' => 50, 'expected_score' => 50],
            ['maxscore' => 50, 'rawscore' => 0, 'expected_score' => 0],
            ['maxscore' => 50, 'rawscore' => 50, 'expected_score' => 100],
            ['maxscore' => 50, 'rawscore' => 25, 'expected_score' => 50],
            ['maxscore' => 200, 'rawscore' => 0, 'expected_score' => 0],
            ['maxscore' => 200, 'rawscore' => 200, 'expected_score' => 100],
            ['maxscore' => 200, 'rawscore' => 100, 'expected_score' => 50],
        ];

        // test attempts with different maxscores and rawscores
        foreach ($test_data as $data) {
            // Create h5p attempt
            $params = [
                'h5pactivityid' => $cm->id,
                'userid' => $this->user->id,
                'rawscore' => $data['rawscore'],
                'maxscore' => $data['maxscore']
            ];
            $generator->create_attempt($params);
            $this->fix_scaled_attribute_of_h5pactivity_attempts();

            // Create grade entry (grade_grades)
            $grader->update_grades();

            // check result
            $this->assertEquals(round($data['expected_score'], 3), round($adler_score->get_score(), 3));
        }


        // test invalid rawscore
        $params = [[
            'h5pactivityid' => $cm->id,
            'userid' => $this->user->id,
            'rawscore' => -1,
            'maxscore' => 100
        ], [
            'h5pactivityid' => $cm->id,
            'userid' => $this->user->id,
            'rawscore' => 101,
            'maxscore' => 100
        ]];
        // use indexed loop
        for ($i = 0; $i < count($params); $i++) {
            $generator->create_attempt($params[$i]);
            $this->fix_scaled_attribute_of_h5pactivity_attempts();

            // Create grade entry (grade_grades)
            $grader->update_grades();

            // check result
            $this->assertEquals($i == 0 ? 0 : $params[$i]['maxscore'], $adler_score->get_score());
        }
    }

    public function test_calculate_percentage_achieved() {
        // test setup
        // create adler_score object without constructor call
        $adler_score = $this->getMockBuilder(adler_score::class)
            ->disableOriginalConstructor()
            ->getMock();

        // make calculate_percentage_achieved public
        $reflection = new ReflectionClass(adler_score::class);
        $method = $reflection->getMethod('calculate_percentage_achieved');
        $method->setAccessible(true);

        // enroll user
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'student');

        // test data
        $test_data = [
            ['min' => 0, 'max' => 100, 'value' => 0, 'expected' => 0],
            ['min' => 0, 'max' => 100, 'value' => 50, 'expected' => .5],
            ['min' => 0, 'max' => 100, 'value' => 100, 'expected' => 1],
            ['min' => 10, 'max' => 20, 'value' => 10, 'expected' => 0],
            ['min' => 10, 'max' => 20, 'value' => 15, 'expected' => .5],
            ['min' => 10, 'max' => 20, 'value' => 20, 'expected' => 1],
            ['min' => 0, 'max' => 100, 'value' => -1, 'expected' => 0],
            ['min' => 0, 'max' => 100, 'value' => 101, 'expected' => 1],
        ];

        // test
        foreach ($test_data as $data) {
            $result = $method->invokeArgs($adler_score, [$data['value'], $data['max'], $data['min']]);
            $this->assertEquals($data['expected'], $result);
        }
    }
}
