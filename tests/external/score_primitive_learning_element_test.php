<?php
namespace local_adler\external;

defined('MOODLE_INTERNAL') || die();

use completion_info;
use external_api;
use externallib_advanced_testcase;
use invalid_parameter_exception;
use local_adler\dsl_score;


global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

class mock_score_primitive_learning_element extends score_primitive_learning_element {
    private static $index = 0;
    private static $data = array();

    public static function set_data(array $data) {
        self::$data = $data;
        self::$index = 0;
    }

    protected static function create_dsl_score_instance($course_module): dsl_score {
        self::$index += 1;
        return self::$data[self::$index - 1];
    }

    public static function call_create_dsl_score_instance($course_module): dsl_score {
        return parent::create_dsl_score_instance($course_module);
    }
}


class score_primitive_learning_element_test extends externallib_advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
        // don't be strict about output
        $this->expectOutputRegex('/.*/');

        // init test data
        $this->course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $this->course_module = $this->getDataGenerator()->create_module('url', array('course' => $this->course->id, 'completion' => 1));
        $this->course_module = get_coursemodule_from_id(null, $this->course_module->cmid, 0, false, MUST_EXIST);
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'student');

        // mock dsl_score class
        $this->mock_dsl_score = $this->getMockBuilder('local_adler\dsl_score')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mock_dsl_score->method('get_score')
            ->willReturn(42.0);
    }

    /** runs after every test */
    public function tearDown(): void {
        parent::tearDown();
        // Moodle thinks debugging messages should be tested (check for debugging messages in unit tests).
        // Imho this is very bad practice, because devs should be encouraged to provide additional Information
        // for debugging. Checking for log messages in tests provides huge additional effort (eg tests will fail because
        // a message was changed / an additional one was added / ...). Because logging should NEVER affect the
        // functionality of the code, this is completely unnecessary. If something went wrong either the code should
        // handle the problem or it should throw an exception.
        $this->resetDebugging();
    }


    public function test_score_primitive_learning_element() {
        // set data for mocked create_dsl_score_instance method
        mock_score_primitive_learning_element::set_data(array($this->mock_dsl_score, $this->mock_dsl_score));

        // Call CUT
        $result = mock_score_primitive_learning_element::execute($this->course_module->id, false);

        // Check result
        $this->assertEquals(42.0, $result['score']);
        $completion = new completion_info($this->course);
        $this->assertFalse((bool)$completion->get_data($this->course_module)->completionstate);

        // Test again with completed = true
        mock_score_primitive_learning_element::execute($this->course_module->id, true);
        $completion = new completion_info($this->course);
        $this->assertTrue((bool)$completion->get_data($this->course_module)->completionstate);
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


        $this->mock_dsl_score->method('get_score')
            ->willReturn(42.0);

        // set data for mocked create_dsl_score_instance method
        mock_score_primitive_learning_element::set_data(array($this->mock_dsl_score));

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage("course_module_is_not_a_primitive_learning_element");

        // Call CUT
        mock_score_primitive_learning_element::execute($this->course_module->id, false);
    }

    public function test_score_primitive_learning_element_user_not_enrolled() {
        // set data for mocked create_dsl_score_instance method
        mock_score_primitive_learning_element::set_data(array($this->mock_dsl_score));

        // create and enroll user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'student');

        $this->expectException('require_login_exception');
        $this->expectExceptionMessageMatches("/Not enrolled/");

        // Call CUT
        mock_score_primitive_learning_element::execute($this->course_module->id, false);
    }

    public function test_score_primitive_learning_element_course_module_not_exist() {
        // set data for mocked create_dsl_score_instance method
        mock_score_primitive_learning_element::set_data(array($this->mock_dsl_score));

        $this->expectException('invalid_parameter_exception');
        $this->expectExceptionMessage("failed_to_get_course_module");

        // Call CUT
        mock_score_primitive_learning_element::execute(987654321, false);
    }

    public function test_execute_returns() {
        external_api::validate_parameters(score_primitive_learning_element::execute_returns(), array(
            'score' => 1.0,
        ));

        $expected_exception = false;
        try {
            external_api::validate_parameters(score_primitive_learning_element::execute_returns(), array(
                'score' => "test",
            ));
        } catch (invalid_parameter_exception $e) {
            $expected_exception = true;
        }
        $this->assertTrue($expected_exception, "Invalid parameter exception not thrown");

        $expected_exception = false;
        try {
            external_api::validate_parameters(score_primitive_learning_element::execute_returns(), array(
                'score' => 1.0,
                'test' => 1.0,
            ));
        } catch (invalid_parameter_exception $e) {
            $expected_exception = true;
        }
        $this->assertTrue($expected_exception, "Invalid parameter exception not thrown");

        $expected_exception = false;
        try {
            external_api::validate_parameters(score_primitive_learning_element::execute_returns(), array());
        } catch (invalid_parameter_exception $e) {
            $expected_exception = true;
        }
        $this->assertTrue($expected_exception, "Invalid parameter exception not thrown");
    }

    public function test_create_dsl_score_instance() {
        $mock = new mock_score_primitive_learning_element();
        $result = $mock->call_create_dsl_score_instance($this->course_module);
        $this->assertInstanceOf(dsl_score::class, $result);
    }
}
