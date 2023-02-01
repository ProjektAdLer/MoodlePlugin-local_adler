<?php

namespace local_adler\external;

global $CFG;

use local_adler\local_adler_externallib_testcase;

require_once($CFG->libdir . '/externallib.php');
//require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/adler/tests/lib.php');

class _score_get_course_scoresTest extends local_adler_externallib_testcase {
    public function setUp(): void {

    }

    public function test_execute_returns() {
        // this function just returns what get_adler_score_response_multiple_structure returns
        require_once(__DIR__ . '/lib_test.php');
        (new _libTest())->test_get_adler_score_response_multiple_structure(score_get_course_scores::class);
    }
}