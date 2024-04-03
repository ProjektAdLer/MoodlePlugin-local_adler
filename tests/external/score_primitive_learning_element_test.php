<?php

namespace local_adler\external;

defined('MOODLE_INTERNAL') || die();

use completion_info;
use external_api;
use invalid_parameter_exception;
use local_adler\adler_score;
use local_adler\lib\adler_externallib_testcase;


global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class score_primitive_learning_element_test extends adler_externallib_testcase {
    // Define the properties explicitly
    public $course;
    public $course_module;
    public $user;
    public $mock_adler_score;

    public function setUp(): void {
        parent::setUp();

        require_once(__DIR__ . '/deprecated_mocks.php');

        // init test data
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        $this->course_module = $this->getDataGenerator()->create_module('url', array('course' => $this->course->id, 'completion' => 1));
        $this->course_module = get_coursemodule_from_id(null, $this->course_module->cmid, 0, false, MUST_EXIST);

        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'student');

        // mock adler_score class
        $this->mock_adler_score = $this->getMockBuilder(adler_score::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mock_adler_score->method('get_score')
            ->willReturn(42.0);
    }


    public function test_score_primitive_learning_element() {
        // set data for mocked create_adler_score_instance method
        mock_score_primitive_learning_element::set_data(array($this->mock_adler_score, $this->mock_adler_score));

        // Call CUT
        $result = mock_score_primitive_learning_element::execute($this->course_module->id, false);

        // Check result
        $this->assertEquals(42.0, $result['data'][0]['score']);
        $completion = new completion_info($this->course);
        $this->assertFalse((bool)$completion->get_data($this->course_module)->completionstate);
        external_api::validate_parameters(  // if this fails an exception will be thrown and the test fails
            mock_score_primitive_learning_element::execute_returns(),
            $result
        );

        // Test again with completed = true
        mock_score_primitive_learning_element::execute($this->course_module->id, true);
        $completion = new completion_info($this->course);
        $this->assertTrue((bool)$completion->get_data($this->course_module)->completionstate);
        external_api::validate_parameters(  // if this fails an exception will be thrown and the test fails
            mock_score_primitive_learning_element::execute_returns(),
            $result
        );
    }

    public function test_score_primitive_learning_element_wrong_datatypes() {
        $exception_thrown = false;
        try {
            mock_score_primitive_learning_element::execute($this->course_module->id, "True");
        } catch (invalid_parameter_exception $e) {
            $exception_thrown = true;
        } finally {
            $this->assertTrue($exception_thrown, "Invalid parameter exception not thrown");
        }

        $exception_thrown = false;
        try {
            mock_score_primitive_learning_element::execute($this->course_module, true);
        } catch (invalid_parameter_exception $e) {
            $exception_thrown = true;
        } finally {
            $this->assertTrue($exception_thrown, "Invalid parameter exception not thrown");
        }
    }

    public function test_score_primitive_learning_element_h5p() {
        // create h5p activity
        $this->course_module = $this->getDataGenerator()->create_module('h5pactivity', array('course' => $this->course->id, 'completion' => 1));
        $this->course_module = get_coursemodule_from_id(null, $this->course_module->cmid, 0, false, MUST_EXIST);

        // set data for mocked create_adler_score_instance method
        mock_score_primitive_learning_element::set_data(array($this->mock_adler_score));

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage("course_module_is_not_a_primitive_learning_element");

        // Call CUT
        mock_score_primitive_learning_element::execute($this->course_module->id, false);
    }

    public function test_score_primitive_learning_element_completion_disabled() {
        // create module with disabled completion
        $course_module = $this->getDataGenerator()->create_module(
            'url',
            array('course' => $this->course->id, 'completion' => 0));

        // expect exception
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage("completion_not_enabled");

        // call CUT
        mock_score_primitive_learning_element::execute($course_module->cmid, true);
    }

    public function test_score_primitive_learning_element_user_not_enrolled() {
        // set data for mocked create_adler_score_instance method
        mock_score_primitive_learning_element::set_data(array($this->mock_adler_score));

        // create and enroll user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException('require_login_exception');
        $this->expectExceptionMessageMatches("/Not enrolled/");

        // Call CUT
        mock_score_primitive_learning_element::execute($this->course_module->id, false);
    }

    public function test_score_primitive_learning_element_course_module_not_exist() {
        // set data for mocked create_adler_score_instance method
        mock_score_primitive_learning_element::set_data(array($this->mock_adler_score));

        $this->expectException('invalid_parameter_exception');
        $this->expectExceptionMessage("failed_to_get_course_module");

        // Call CUT
        mock_score_primitive_learning_element::execute(987654321, false);
    }

    public function test_execute_returns() {
        // this function just returns what get_adler_score_response_multiple_structure returns
        require_once(__DIR__ . '/lib_test.php');
        (new lib_test())->test_get_adler_score_response_multiple_structure(score_primitive_learning_element::class);
    }

    public function test_create_adler_score_instance() {
        $mock = new mock_score_primitive_learning_element();
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage("local_adler/not_an_adler_course");
        $mock->call_create_adler_score_instance($this->course_module);
    }
}
