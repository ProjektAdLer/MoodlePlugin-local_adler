<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local\db;

global $CFG;

use local_adler\lib\adler_testcase;
use local_adler\local\db\adler_course_repository;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class adler_course_repository_test extends adler_testcase {
    public function test_create_adler_course() {
        global $DB;
        $adler_course_repository = new adler_course_repository();

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
        $record = $DB->get_record('local_adler_course', ['id' => $id]);

        // validate record was created
        $this->assertNotEmpty($record);
        $this->assertEquals($moodle_course->id, $record->course_id);
    }
}