<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local\db;

global $CFG;

use dml_exception;
use local_adler\lib\adler_testcase;
use local_adler\local\db\adler_course_module_repository;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class adler_sections_repository_test extends adler_testcase {
    public function test_create_adler_cm() {
        global $DB;
        $adler_course_module_repository = new adler_course_module_repository();

        // create course
        $course = $this->getDataGenerator()->create_course();

        // create cm
        $cm = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // create adler_cm object
        $adler_cm = (object)[
            'cmid' => $cm->cmid,
            'score_max' => 100,
        ];

        // call function
        $id = $adler_course_module_repository->create_adler_cm($adler_cm);

        // query db for the returned id
        $record = $DB->get_record('local_adler_course_modules', ['id' => $id]);

        // validate record was created
        $this->assertNotEmpty($record);
        $this->assertEquals($adler_cm->cmid, $record->cmid);
        $this->assertEquals($adler_cm->score_max, $record->score_max);
    }

    /**
     * # ANF-ID: [MVP12, MVP10, MVP9, MVP8, MVP7]
     */
    public function test_get_adler_score_record() {
        $adler_course_module_repository = new adler_course_module_repository();

        // create course
        $course = $this->getDataGenerator()->create_course();

        // create cm
        $cm = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // create adler score item
        $adler_score_item = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($cm->cmid);

        // call function
        $result = $adler_course_module_repository->get_adler_course_module_by_cmid($cm->cmid);

        // check result
        $this->assertEquals($adler_score_item->id, $result->id);
        $this->assertEquals($adler_score_item->cmid, $result->cmid);
        $this->assertEquals($adler_score_item->score_max, $result->score_max);


        // error case
        $this->expectException(dml_exception::class);

        // create cm
        $cm = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        // call function
        $adler_course_module_repository->get_adler_course_module_by_cmid($cm->cmid);
    }

    public function test_delete_adler_course_module_record() {
        global $DB;
        $adler_course_module_repository = new adler_course_module_repository();

        // create course
        $course = $this->getDataGenerator()->create_course();

        // create cm
        $cm = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // create adler score item
        $adler_score_item = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($cm->cmid);

        // validate there is 1 record
        $this->assertEquals(1, $DB->count_records('local_adler_course_modules'));

        // call function
        $adler_course_module_repository->delete_adler_score_record_by_cmid($cm->cmid);

        // validate there are 0 records
        $this->assertEquals(0, $DB->count_records('local_adler_course_modules'));
    }

    public function provide_record_for_cmid_exists_data(): array {
        return [
            'record exists' => [true],
            'record does not exist' => [false]
        ];
    }

    /**
     * @dataProvider provide_record_for_cmid_exists_data
     */
    public function test_record_for_cmid_exists($record_exists) {
        $adler_course_module_repository = new adler_course_module_repository();

        // create course
        $course = $this->getDataGenerator()->create_course();

        // create cm
        $cm = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // create adler score item
        if ($record_exists) {
            $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($cm->cmid);
        }

        // call function
        $result = $adler_course_module_repository->record_for_cmid_exists($cm->cmid);

        // check result
        $this->assertEquals($record_exists, $result);
    }
}