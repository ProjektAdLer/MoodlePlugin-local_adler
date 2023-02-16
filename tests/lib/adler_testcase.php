<?php

namespace local_adler\lib;

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/adler/tests/lib/static_mock_framework.php');
require_once($CFG->dirroot . '/local/adler/classes/exceptions.php');

use advanced_testcase;
use externallib_advanced_testcase;

trait general_testcase_adjustments{
    public function setUp(): void {
        parent::setUp();

        // set default value: reset DB after each test case
        $this->resetAfterTest();
    }

    public function tearDown(): void {
        parent::tearDown();

        // Moodle thinks debugging messages should be tested (check for debugging messages in unit tests).
        // Imho this is very bad practice, because devs should be encouraged to provide additional Information
        // for debugging. Checking for log messages in tests provides huge additional effort (e.g. tests will fail because
        // a message was changed / an additional one was added / ...). Because logging should NEVER affect the
        // functionality of the code, this is completely unnecessary. Where this leads can be perfectly seen in all
        // moodle code: Things work or do not work and there is no feedback on that. Often things return null if successfully
        // and if nothing happened (also categorized as successful), but no feedback is available which of both cases happened.
        // Users and devs very often can't know why something does not work.
        // If something went wrong either the code should handle the problem or it should throw an exception.
        $this->resetDebugging();
    }
}

abstract class local_adler_testcase extends advanced_testcase {
    use general_testcase_adjustments;
}

abstract class local_adler_externallib_testcase extends externallib_advanced_testcase {
    use general_testcase_adjustments;
}