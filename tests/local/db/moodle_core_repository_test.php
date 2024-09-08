<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler;

global $CFG;

use local_adler\lib\adler_testcase;
use local_adler\local\db\moodle_core_repository;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class moodle_core_repository_test extends adler_testcase {
    public function test_get_role_id_by_shortname() {
        $moodle_core_repository = new moodle_core_repository();

        // call function
        $result = $moodle_core_repository->get_role_id_by_shortname('student');

        // check result
        $this->assertEquals(5, $result);

        // error case
        // call function
        $result = $moodle_core_repository->get_role_id_by_shortname('nonexistentrole');

        $this->assertEquals(false, $result);
    }

    public function test_get_user_id_by_username() {
        $moodle_core_repository = new moodle_core_repository();

        // call function
        $result = $moodle_core_repository->get_user_id_by_username('admin');

        // check result
        $this->assertEquals(2, $result);

        // error case
        // call function
        $result = $moodle_core_repository->get_user_id_by_username('nonexistentuser');

        $this->assertEquals(false, $result);
    }
}