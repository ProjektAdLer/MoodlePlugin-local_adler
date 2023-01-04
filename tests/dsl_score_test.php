<?php

namespace local_adler;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use completion_info;

class dsl_score_test extends advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        global $DB;


        // cleanup after every test
        $this->resetAfterTest(true);

        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();
        $this->course_without_dsl_data = $this->getDataGenerator()->create_course();

        // Create a modules.
        $this->module_db_format = $this->getDataGenerator()->create_module('url', ['course' => $this->course->id]);
        $this->module = get_fast_modinfo($this->course->id, 0 , false)->get_cm($this->module_db_format->cmid);

//        $this->h5p_module = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $this->course->id]);
        $this->module_without_dsl_data = $this->getDataGenerator()->create_module('url', ['course' => $this->course_without_dsl_data->id]);
        $this->module_without_dsl_data = get_fast_modinfo($this->course_without_dsl_data->id, 0 , false)->get_cm($this->module_without_dsl_data->cmid);

//        $this->h5p_module_without_dsl_data = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $this->course_without_dsl_data->id]);


        // Create score (dsl) items.
        $this->score_item_primitive = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_dsl_score_item($this->module->id);
//        $this->score_item_h5p = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_dsl_score_item($this->h5p_module->cmid);

        // Create user.
        $this->user = $this->getDataGenerator()->create_user();
    }

    /** runs after every test */
    public function tearDown(): void {
        parent::tearDown();
        // Moodle thinks debugging messages should be tested (check for debugging messages in unit tests).
        // Imho this is very bad practice, because devs should be encouraged to provide additional Information
        // for debugging. Checking for log messages in tests provides huge additional effort (eg tests will fail because
        // a message was changed / an additional one was added / ...). Because logging should NEVER affect the
        // functionality of the code, this is completely unnecessary. If something went wrong either the code should
        // handle the problem or it should throw an exception.
        $this->resetDebugging();
    }

    public function test_create_dsl_score_with_wrong_course_format() {
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('local_adler/course_module_format_not_valid');

        $dsl_score = new dsl_score($this->module_db_format, $this->user->id);
    }

    public function test_user_not_enrolled_in_course() {
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('local_adler/user_not_enrolled');

        $dsl_score = new dsl_score($this->module, $this->user->id);
    }

    public function test_get_score_for_primitive_learning_element() {
        // enroll user in course
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'student');

        $dsl_score = new dsl_score($this->module, $this->user->id);
        $completion = new completion_info($this->course);

        // test empty submission
        $this->assertEquals($this->score_item_primitive->score_min, $dsl_score->get_score());

        // test completion entry exists with entry: false
        $completion->update_state($this->module, COMPLETION_INCOMPLETE);
        $this->assertEquals($this->score_item_primitive->score_min, $dsl_score->get_score());

        // test completion entry exists with entry: true
        $completion->update_state($this->module, COMPLETION_COMPLETE);
        $this->assertEquals($this->score_item_primitive->score_min, $dsl_score->get_score());
    }

    public function test_get_score_for_primitive_learning_element_with_global_USER_obejct() {
        // enroll user in course
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'student');

        // initialize user object
        global $USER, $DB;
        $USER = $DB->get_record('user', ['id' => $this->user->id]);
        // Don't be strict about output for this test. completionlib is using deprecated functions. I can not change this.
        $this->expectOutputRegex('/.*/');

        $dsl_score = new dsl_score($this->module);

        // test empty submission
        $this->assertEquals($this->score_item_primitive->score_min, $dsl_score->get_score());
    }

    public function test_get_score_for_primitive_learning_element_no_dsl_metadata() {
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course_without_dsl_data->id, 'student');

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('local_adler/scores_items_not_found');

        $dsl_score = new dsl_score($this->module_without_dsl_data, $this->user->id);
        $dsl_score->get_score();
    }
}