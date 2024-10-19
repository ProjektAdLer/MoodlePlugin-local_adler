<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler;

global $CFG;

use core\di;
use dml_exception;
use local_adler\lib\adler_testcase;
use local_adler\local\db\adler_course_module_repository;
use moodle_database;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class adler_course_module_repository_test extends adler_testcase {
    public function test_create_adler_cm() {
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
        $id = di::get(adler_course_module_repository::class)->create_adler_cm($adler_cm);

        // query db for the returned id
        $record = di::get(moodle_database::class)->get_record('local_adler_course_modules', ['id' => $id]);

        // validate record was created
        $this->assertNotEmpty($record);
        $this->assertEquals($adler_cm->cmid, $record->cmid);
        $this->assertEquals($adler_cm->score_max, $record->score_max);
    }

    /**
     * # ANF-ID: [MVP12, MVP10, MVP9, MVP8, MVP7]
     */
    public function test_get_adler_score_record() {
        // create course
        $course = $this->getDataGenerator()->create_course();

        // create cm
        $cm = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // create adler score item
        $adler_score_item = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($cm->cmid);

        // call function
        $result = di::get(adler_course_module_repository::class)->get_adler_course_module_by_cmid($cm->cmid);

        // check result
        $this->assertEquals($adler_score_item->id, $result->id);
        $this->assertEquals($adler_score_item->cmid, $result->cmid);
        $this->assertEquals($adler_score_item->score_max, $result->score_max);


        // error case
        $this->expectException(dml_exception::class);

        // create cm
        $cm = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        // call function
        di::get(adler_course_module_repository::class)->get_adler_course_module_by_cmid($cm->cmid);
    }

    public function test_delete_adler_course_module_record() {
        // create course
        $course = $this->getDataGenerator()->create_course();

        // create cm
        $cm = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // create adler score item
        $adler_score_item = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($cm->cmid);

        // validate there is 1 record
        $this->assertEquals(1, di::get(moodle_database::class)->count_records('local_adler_course_modules'));

        // call function
        di::get(adler_course_module_repository::class)->delete_adler_course_module_by_cmid($cm->cmid);

        // validate there are 0 records
        $this->assertEquals(0, di::get(moodle_database::class)->count_records('local_adler_course_modules'));
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
        // create course
        $course = $this->getDataGenerator()->create_course();

        // create cm
        $cm = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // create adler score item
        if ($record_exists) {
            $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($cm->cmid);
        }

        // call function
        $result = di::get(adler_course_module_repository::class)->record_for_cmid_exists($cm->cmid);

        // check result
        $this->assertEquals($record_exists, $result);
    }

    public function provide_test_get_adler_course_module_by_uuid_data() {
        return [
            'success' => [
                'success' => true,
            ],
            'exception' => [
                'success' => false,
            ]
        ];
    }

    /**
     * @dataProvider provide_test_get_adler_course_module_by_uuid_data
     *
     * # ANF-ID: [MVP6]
     */
    public function test_get_adler_course_module_by_uuid($success) {
        $adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');

        // create course
        $course = $this->getDataGenerator()->create_course();

        // create module
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        // create adler_section entry
        if ($success) {
            $adler_cm = $adler_generator->create_adler_course_module($module->cmid);
        } else {
            $adler_cm = $adler_generator->create_adler_course_module(123456789);
            $this->expectException(dml_exception::class);
        }

        // call function
        $result = di::get(adler_course_module_repository::class)->get_adler_course_module($adler_cm->uuid, $course->id);

        // check result
        $this->assertEquals($adler_cm, $result);
    }

    public function test_get_all_adler_course_modules() {
        $adler_course_module_repository = di::get(adler_course_module_repository::class);

        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Create multiple Adler course modules
        $adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');
        $adler_cm1 = $adler_generator->create_adler_course_module($this->getDataGenerator()->create_module('assign', ['course' => $course->id])->cmid);
        $adler_cm2 = $adler_generator->create_adler_course_module($this->getDataGenerator()->create_module('forum', ['course' => $course->id])->cmid);

        // Call the method
        $result = $adler_course_module_repository->get_all_adler_course_modules();

        // Check the result
        $this->assertArrayHasKey($adler_cm1->id, $result);
        $this->assertArrayHasKey($adler_cm2->id, $result);
        $this->assertEquals($adler_cm1->id, $result[$adler_cm1->id]->id);
        $this->assertEquals($adler_cm2->id, $result[$adler_cm2->id]->id);
    }
}