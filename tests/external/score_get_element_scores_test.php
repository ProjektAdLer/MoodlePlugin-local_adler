<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler\external;

defined('MOODLE_INTERNAL') || die();


use core\di;
use dml_missing_record_exception;
use local_adler\adler_score_helpers;
use local_adler\lib\adler_externallib_testcase;
use local_adler\moodle_core;
use Mockery;
use moodle_exception;
use require_login_exception;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


class score_get_element_scores_test extends adler_externallib_testcase {
    /**
     * # ANF-ID: [MVP8]
     */
    public function test_execute() {
        // define test data
        $module_ids = [1, 2, 42];

        // setup mocks
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock
            ->shouldReceive('context_module_instance')
            ->andReturn((object)['id' => 1], (object)['id' => 2], (object)['id' => 3]);
        di::set(moodle_core::class, $moodle_core_mock);

        $adler_score_helpers_mock = Mockery::mock(adler_score_helpers::class);
        $adler_score_helpers_mock->shouldReceive('get_adler_score_objects')->andReturn([
            1 => Mockery::mock('adler_score', [
                'get_cmid' => 1,
                'get_completion_state' => true,
                'get_score_by_completion_state' => 0.0
            ]),
            2 => Mockery::mock('adler_score', [
                'get_cmid' => 2,
                'get_completion_state' => true,
                'get_score_by_completion_state' => 5.0
            ]),
            42 => Mockery::mock('adler_score', [
                'get_cmid' => 42,
                'get_completion_state' => true,
                'get_score_by_completion_state' => 42.0
            ])
        ]);
        di::set(adler_score_helpers::class, $adler_score_helpers_mock);

        $score_get_element_scores_mock = Mockery::mock(score_get_element_scores::class)->makePartial();
        $score_get_element_scores_mock
            ->shouldReceive('validate_context');

        // call function
        $result = $score_get_element_scores_mock::execute($module_ids);

        // check return value
        $this->assertEqualsCanonicalizing([['moduleid' => 1, 'score' => 0.0, 'completed' => true], ['moduleid' => 2, 'score' => 5.0, 'completed' => true], ['moduleid' => 42, 'score' => 42.0, 'completed' => true]], $result['data']);

        $this->assertEquals(3, count($result['data']));
        $this->assertEquals(1, $result['data'][0]['module_id']);
        $this->assertEquals(0.0, $result['data'][0]['score']);
        $this->assertEquals(2, $result['data'][1]['module_id']);
        $this->assertEquals(5.0, $result['data'][1]['score']);
        $this->assertEquals(42, $result['data'][2]['module_id']);
        $this->assertEquals(42.0, $result['data'][2]['score']);
    }

    public function execute_exceptions_provider() {
        return [
            'missing_record_exception' => [
                'module_ids' => [1],
                'exception' => dml_missing_record_exception::class,
            ],
            'require_login_exception' => [
                'module_ids' => [2],
                'exception' => require_login_exception::class,
            ],
        ];
    }

    /**
     * @dataProvider execute_exceptions_provider
     * # ANF-ID: [MVP8]
     */
    public function test_execute_exceptions($module_ids, $exception) {
        // Mocking the necessary methods
        $score_get_element_scores_mock = Mockery::mock(score_get_element_scores::class)->makePartial();

        $moodle_core_mock = Mockery::mock(moodle_core::class);
        di::set(moodle_core::class, $moodle_core_mock);

        if ($exception === dml_missing_record_exception::class) {
            $moodle_core_mock->shouldReceive('context_module_instance')
                ->andThrow(dml_missing_record_exception::class);
        } elseif ($exception === require_login_exception::class) {
            $moodle_core_mock->shouldReceive('context_module_instance')
                ->andReturn((object)[]);
            $score_get_element_scores_mock->shouldReceive('validate_context')
                ->andThrow(require_login_exception::class);
        }
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('invalidmoduleids');

        $score_get_element_scores_mock::execute($module_ids);
    }

    /**
     * # ANF-ID: [MVP8]
     */
    public function test_execute_partial_failure() {
        // define test data
        $module_ids = [1, 2];

        // setup mocks
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock
            ->shouldReceive('context_module_instance')
            ->andThrow(dml_missing_record_exception::class, [1]) // First module ID fails
            ->shouldReceive('context_module_instance')
            ->andReturn((object)['id' => 2]); // Second module ID passes
        di::set(moodle_core::class, $moodle_core_mock);

        $adler_score_helpers_mock = Mockery::mock(adler_score_helpers::class);
        $adler_score_helpers_mock->shouldReceive('get_completion_state_and_achieved_scores')->andReturn([2 => ['score' => 5.0, 'completion_state' => true]]);
        di::set(adler_score_helpers::class, $adler_score_helpers_mock);

        $score_get_element_scores_mock = Mockery::mock(score_get_element_scores::class)->makePartial();
        $score_get_element_scores_mock
            ->shouldReceive('validate_context');

        // call function and expect exception
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('invalidmoduleids');

        $score_get_element_scores_mock::execute($module_ids);
    }


    /**
     * # ANF-ID: [MVP8]
     */
    public function test_execute_returns() {
        // this function just returns what get_adler_score_response_multiple_structure returns
        require_once(__DIR__ . '/lib_test.php');
        (new lib_test(''))->test_get_adler_score_response_multiple_structure(score_get_element_scores::class);
    }
}