<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local\db;

global $CFG;

use core\di;
use local_adler\lib\adler_testcase;
use local_adler\local\db\adler_course_repository;
use moodle_database;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class adler_course_repository_test extends adler_testcase {
    public function test_create_adler_course() {
        $adler_course_repository = di::get(adler_course_repository::class);

        // create moodle course
        $moodle_course = $this->getDataGenerator()->create_course();

        // create course object
        $course = (object)[
            'course_id' => $moodle_course->id,
            'uuid' => '1234',
        ];

        // call function
        $id = $adler_course_repository->create_adler_course($course);

        // query db for the returned id
        $record = di::get(moodle_database::class)->get_record('local_adler_course', ['id' => $id]);

        // validate record was created
        $this->assertNotEmpty($record);
        $this->assertEquals($moodle_course->id, $record->course_id);
    }

    public function test_delete_adler_course_by_course_id() {
        $adler_course_repository = di::get(adler_course_repository::class);

        // create moodle course
        $moodle_course = $this->getDataGenerator()->create_course();

        // create course object
        $course = (object)[
            'course_id' => $moodle_course->id,
            'uuid' => '1234',
        ];

        // insert record
        $id = $adler_course_repository->create_adler_course($course);

        // ensure record exists
        $record = di::get(moodle_database::class)->get_record('local_adler_course', ['id' => $id]);
        $this->assertNotEmpty($record);

        // delete record
        $adler_course_repository->delete_adler_course_by_moodle_course_id($moodle_course->id);

        // ensure record no longer exists
        $record = di::get(moodle_database::class)->get_record('local_adler_course', ['id' => $id]);
        $this->assertEmpty($record);
    }

    public function test_get_adler_course_by_moodle_course_id() {
        $adler_course_repository = di::get(adler_course_repository::class);

        // create moodle course
        $moodle_course = $this->getDataGenerator()->create_course();

        // create course object
        $course = (object)[
            'course_id' => $moodle_course->id,
            'uuid' => '1234',
        ];

        // insert record
        $id = $adler_course_repository->create_adler_course($course);

        // retrieve record using the method
        $retrieved_course = $adler_course_repository->get_adler_course_by_moodle_course_id($moodle_course->id);

        // validate retrieved record
        $this->assertNotEmpty($retrieved_course);
        $this->assertEquals($moodle_course->id, $retrieved_course->course_id);
        $this->assertEquals('1234', $retrieved_course->uuid);
    }
}