<?php

namespace local_adler\external;

defined('MOODLE_INTERNAL') || die();


use dml_missing_record_exception;
use invalid_parameter_exception;
use local_adler\dsl_score_helpers_mock;
use local_adler\lib\local_adler_externallib_testcase;
use moodle_exception;
use require_login_exception;
use Throwable;

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');
require_once($CFG->dirroot . '/local/adler/tests/mocks.php');
require_once('generic_mocks.php');

class score_get_element_scores_mock extends score_get_element_scores {
    use external_api_validate_context_trait;

    protected static $dsl_score_helpers = dsl_score_helpers_mock::class;
    protected static $context_module = context_module_mock::class;
}


class score_get_element_scores_test extends local_adler_externallib_testcase {
    public function test_execute() {
        // define test data
        $module_ids = [1, 2, 42];

        // setup mocks
        score_get_element_scores_mock::reset_data();

        context_module_mock::reset_data();
        context_module_mock::set_returns('instance', range(1,3));

        dsl_score_helpers_mock::reset_data();
        dsl_score_helpers_mock::set_enable_mock('get_achieved_scores', true);
        dsl_score_helpers_mock::set_returns('get_achieved_scores', [[1=>0, 2=>5.0, 42=>42.0]]);

        $result = score_get_element_scores_mock::execute($module_ids);

        // check return value
        $this->assertEqualsCanonicalizing([['moduleid'=>1, 'score'=>0.0], ['moduleid'=>2, 'score'=>5.0], ['moduleid'=>42, 'score'=>42.0]], $result['data']);
        // check function calls
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($module_ids[$i], context_module_mock::get_calls('instance')[$i][0]);
        }
        $this->assertEquals($module_ids, dsl_score_helpers_mock::get_calls('get_achieved_scores')[0][0]);
    }

    public function test_execute_exceptions() {
        // testcases
        // call parameter fails
        // module does not exist
        // user not enrolled
        // dsl_score::get_achieved_scores fails
        // return data validation fails

        $test_configuration = [
            'context_validation' => [
                'exceptions' => [
                    null,
                    null,
                    new require_login_exception('test'),
                    null,
                    null,
                ],
                'returns' => [
                    null,
                    null,
                    null,
                    null,
                    null,
                ],
            ],
            'context_module::instance' => [
                'exceptions' => [
                    null,
                    new dml_missing_record_exception('test'),
                    null,
                    null,
                    null,
                ],
                'returns' => [
                    null,
                    null,
                    null,
                    null,
                    null,
                ],
            ],
            'dsl_score::get_achieved_scores' => [
                'exceptions' => [
                    null,
                    null,
                    null,
                    new moodle_exception('test'),
                    null,
                ],
                'returns' => [
                    [1=>0],
                    [1=>0],
                    [1=>0],
                    [1=>0],
                    ["Lorem ipsum"]
                ],
            ],
        ];

        // call parameters
        $params = [
            ["Lorem ipsum"],
            [1],
            [1],
            [1],
            [1],
        ];

        // expected exceptions
        $expectExceptions = [
            [invalid_parameter_exception::class, ''],
            [moodle_exception::class, 'invalidmoduleids'],
            [moodle_exception::class, 'invalidmoduleids'],
            [moodle_exception::class, ''],
            [invalid_parameter_exception::class, ''],
        ];

        // run tests
        for ($i = 0; $i < count($params); $i++) {
            // setup mocks
            // setup context validation
            score_get_element_scores_mock::reset_data();
            score_get_element_scores_mock::set_exceptions('validate_context', [
                $test_configuration['context_validation']['exceptions'][$i],
            ]);
            score_get_element_scores_mock::set_returns('validate_context', [
                $test_configuration['context_validation']['returns'][$i],
            ]);

            // setup context_module::instance
            context_module_mock::reset_data();
            context_module_mock::set_exceptions('instance', [
                $test_configuration['context_module::instance']['exceptions'][$i],
            ]);
            context_module_mock::set_returns('instance', [
                $test_configuration['context_module::instance']['returns'][$i],
            ]);

            // setup dsl_score::get_achieved_scores
            dsl_score_helpers_mock::reset_data();
            dsl_score_helpers_mock::set_enable_mock('get_achieved_scores', true);
            dsl_score_helpers_mock::set_exceptions('get_achieved_scores', [
                $test_configuration['dsl_score::get_achieved_scores']['exceptions'][$i],
            ]);
            dsl_score_helpers_mock::set_returns('get_achieved_scores', [
                $test_configuration['dsl_score::get_achieved_scores']['returns'][$i],
            ]);

            try {
                score_get_element_scores_mock::execute($params[$i]);
            } catch (Throwable $e) {
                $this->assertEquals($expectExceptions[$i][0], get_class($e),'$i = '.$i);
                $this->assertStringContainsString($expectExceptions[$i][1], $e->errorcode, '$i = '.$i);
                continue;
            }
            $this->fail('Exception expected, $i = '.$i);
        }
    }

    public function test_execute_returns() {
        // this function just returns what get_adler_score_response_multiple_structure returns
        require_once(__DIR__ . '/lib_test.php');
        (new lib_test())->test_get_adler_score_response_multiple_structure(score_get_element_scores::class);
    }
}