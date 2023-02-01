<?php

namespace local_adler\external;

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


use external_api;
use invalid_parameter_exception;
use local_adler\lib\local_adler_testcase;

class lib_test extends local_adler_testcase {
    /**
     * @param $class_to_test string|null Exactly this function is required in different API classes because they require a proxy function to this function. Therefore, this test is also valid for those API classes. This parameter allows exactly this. Pass the class name via classname::class.
     * @return void
     */
    public function test_get_adler_score_response_multiple_structure($class_to_test = null) {
        $testcases = [
            [
                'expect_success' => true,
                'data' => [[
                    'score' => 1.0,
                    'module_id' => 1,
                ]],
            ],
            [
                'expect_success' => true,
                'data' => [[
                    'module_id' => 1,
                ]],
            ],
            [
                'expect_success' => false,
                'data' => [[
                    'score' => 1.0,
                    'module_id' => 1,
                    'test' => 1,
                ]],
            ],
            [
                'expect_success' => false,
                'data' => [[
                    'score' => "test",
                    'module_id' => 1,
                ]],
            ],
            [
                'expect_success' => false,
                'data' => [[
                    'score' => 1.0,
                    'module_id' => "test",
                ]],
            ],
            [
                'expect_success' => false,
                'data' => [[
                    'score' => 1.0,
                ]],
            ],
            [
                'expect_success' => false,
                'data' => [
                    'score' => 1.0,
                    'module_id' => 1,
                ],
            ],
//            // These cases should fail, but they don't. The validation function seems to ignore VALUE_REQUIRED for
//            // external_single/multiple_structure. Moodle logic...
//            [
//                'expect_success' => false,
//                'data' => [[]],
//            ],
//            [
//                'expect_success' => false,
//                'data' => [],
//            ],
            [
                'expect_success' => false,
                'data' => null,
            ],
            [
                'expect_success' => true,
                'data' => [[
                    'score' => 1.0,
                    'module_id' => 1,
                ], [
                    'score' => 1.0,
                    'module_id' => 1,
                ]],
            ]
        ];

        for ($i = 0; $i < count($testcases); $i++) {
            $exception_thrown = false;
            try {
                if ($class_to_test == null)
                    external_api::validate_parameters(lib::get_adler_score_response_multiple_structure(), ['data' => $testcases[$i]['data']]);
                else
                    external_api::validate_parameters($class_to_test::execute_returns(), ['data' => $testcases[$i]['data']]);
            } catch (invalid_parameter_exception $e) {
                $exception_thrown = true;
            }
            $this->assertEquals(
                $testcases[$i]['expect_success'],
                !$exception_thrown,
                ($testcases[$i]['expect_success'] ? "Unexpected Exception " : "Expected Exception, but not thrown ") . "for testcase " . $i . "\n"
                . "test data: \n"
                . json_encode($testcases[$i]['data']));
        }
    }

    public function test_convert_adler_score_array_format_to_response_structure() {
        $testcases = [
            [
                'expected' => [[
                    'module_id' => 1,
                    'score' => 1.0,
                ]],
                'test' => [1 => 1.0]
            ],
            [
                'expected' => [[
                    'module_id' => 1,
                    'score' => 1.0,
                ], [
                    'module_id' => 2,
                    'score' => 2.0,
                ]],
                'test' => [1 => 1.0, 2 => 2.0]
            ],
            [
                'expected' => [[
                    'module_id' => 1,
                ]],
                'test' => [1 => false]
            ],
        ];

        for ($i = 0; $i < count($testcases); $i++) {
            $result = lib::convert_adler_score_array_format_to_response_structure($testcases[$i]['test']);
            $this->assertEquals($testcases[$i]['expected'], $result);
        }

        // test invalid parameter exception
        $this->expectException(invalid_parameter_exception::class);
        lib::convert_adler_score_array_format_to_response_structure(["test"]);
    }
}