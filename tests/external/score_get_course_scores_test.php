<?php

namespace local_adler\external;

global $CFG;

use local_adler\lib\local_adler_externallib_testcase;
use require_login_exception;

require_once($CFG->libdir . '/externallib.php');
//require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');
require_once('generic_mocks.php');


class score_get_course_scores_mock extends score_get_course_scores {
    use external_api_validate_context_trait_new;

    protected static $dsl_score = dsl_score_mock_new::class;
    protected static $context_course = context_course_mock_new::class;
}

class score_get_course_scores_test extends local_adler_externallib_testcase {
    private function create_course_with_modules(int $module_count) {
        $course = $this->getDataGenerator()->create_course();
        $modules = [];
        for ($i = 0; $i < $module_count; $i++) {
            $modules[] = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        }
        return [$course, $modules];
    }

    public function test_execute() {
        // cant mock get_fast_modinfo, so create course with modules
        [$course, $modules] = $this->create_course_with_modules(3);
        $module_ids = array_map(function ($module) {
            return $module->cmid;
        }, $modules);

        // setup mocks
        score_get_course_scores_mock::reset_data();
        score_get_course_scores_mock::set_exceptions('validate_context', [null, new require_login_exception('test')]);

        context_course_mock_new::reset_data();
        context_course_mock_new::set_returns('instance', [1, null]);

        dsl_score_mock_new::reset_data();
        foreach ($module_ids as $module_id) {
            $dsl_return_data[$module_id] = $module_id * 2;
        }
        dsl_score_mock_new::set_returns('get_achieved_scores', [$dsl_return_data]);

        // 1st call: success
        $result = score_get_course_scores_mock::execute($course->id);

        // check return value
        $this->assertEqualsCanonicalizing([
            ['moduleid' => $module_ids[0], 'score' => $dsl_return_data[$module_ids[0]]],
            ['moduleid' => $module_ids[1], 'score' => $dsl_return_data[$module_ids[1]]],
            ['moduleid' => $module_ids[2], 'score' => $dsl_return_data[$module_ids[2]]],
        ], $result['data']);
        // check function calls
        $this->assertEquals($course->id, context_course_mock_new::get_calls('instance')[0][0]);
        $this->assertEquals($module_ids, dsl_score_mock_new::get_calls('get_achieved_scores')[0][0]);

        // 2nd call: fail
        $this->expectException(require_login_exception::class);
        score_get_course_scores_mock::execute($course->id);
    }

    public function test_execute_returns() {
        // this function just returns what get_adler_score_response_multiple_structure returns
        require_once(__DIR__ . '/lib_test.php');
        (new lib_test())->test_get_adler_score_response_multiple_structure(score_get_course_scores::class);
    }
}