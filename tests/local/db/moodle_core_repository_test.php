<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler;

global $CFG;

use dml_exception;
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

    public function provide_true_false_data() {
        return [
            'true' => [true],
            'false' => [false]
        ];
    }

    /**
     * @dataProvider provide_true_false_data
     */
    public function test_get_grade_item($exists) {
        $moodle_core_repository = new moodle_core_repository();

        if ($exists) {
            // create grade_item
            $course = $this->getDataGenerator()->create_course();
            $grade_item = $this->getDataGenerator()->create_grade_item(['courseid' => $course->id, 'itemmodule' => 'url', 'iteminstance' => 1]);

            // call function
            $result = $moodle_core_repository->get_grade_item($grade_item->itemmodule, $grade_item->iteminstance);

            // check result
            $this->assertEquals($grade_item->id, $result->id);
        } else {
            // error case
            $this->expectException(dml_exception::class);

            // call function
            $moodle_core_repository->get_grade_item('url', 1);
        }
    }

    public function test_update_grade_item_record() {
        global $DB;
        $moodle_core_repository = new moodle_core_repository();

        // create grade_item
        $course = $this->getDataGenerator()->create_course();
        $grade_item = $this->getDataGenerator()->create_grade_item(['courseid' => $course->id, 'itemmodule' => 'url', 'iteminstance' => 1]);

        // call function
        $moodle_core_repository->update_grade_item_record($grade_item->id, ['gradepass' => 100]);

        // check result
        $result = $DB->get_record('grade_items', ['id' => $grade_item->id]);
        $this->assertEquals(100, $result->gradepass);
    }

    public function test_update_course_module_record() {
        global $DB;
        $moodle_core_repository = new moodle_core_repository();

        // create course_module
        $course = $this->getDataGenerator()->create_course();
        $course_module = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // call function
        $moodle_core_repository->update_course_module_record($course_module->cmid, ['completion' => 2]);

        // check result
        $result = $DB->get_record('course_modules', ['id' => $course_module->cmid]);
        $this->assertEquals(2, $result->completion);
    }

    public function test_get_cms_with_module_name_by_course_id() {
        $moodle_core_repository = new moodle_core_repository();

        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Create a course module
        $course_module = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // Call the function
        $result = $moodle_core_repository->get_cms_with_module_name_by_course_id($course->id);

        // Check the result
        $this->assertCount(1, $result);
        $this->assertEquals($course_module->id, reset($result)->instance);
        $this->assertEquals('url', reset($result)->modname);
    }
}