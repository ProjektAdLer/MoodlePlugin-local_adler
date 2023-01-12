<?php

namespace local_adler;

use advanced_testcase;
use completion_info;
use mod_h5pactivity\local\grader;

class dsl_score_test extends advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        // cleanup after every test
        $this->resetAfterTest(true);

        // Create a course.
        $this->course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $this->course_without_dsl_data = $this->getDataGenerator()->create_course();

        // Create user.
        $this->user = $this->getDataGenerator()->create_user();
        // Set current user. Required for h5p generator and completion->update_state (as default value).
        $this->setUser($this->user);

        // Create a modules.
        $this->module_db_format = $this->getDataGenerator()->create_module('url', ['course' => $this->course->id, 'completion' => 1]);
        $this->module = get_fast_modinfo($this->course->id, 0, false)->get_cm($this->module_db_format->cmid);

        $this->module_without_dsl_data = $this->getDataGenerator()->create_module('url', ['course' => $this->course_without_dsl_data->id]);
        $this->module_without_dsl_data = get_fast_modinfo($this->course_without_dsl_data->id, 0, false)->get_cm($this->module_without_dsl_data->cmid);

        // Create score (dsl) items.
        $this->score_item_primitive = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_dsl_score_item($this->module->id);
    }

    /** runs after every test */
    public function tearDown(): void {
        parent::tearDown();
        // Moodle thinks debugging messages should be tested (check for debugging messages in unit tests).
        // Imho this is very bad practice, because devs should be encouraged to provide additional Information
        // for debugging. Checking for log messages in tests provides huge additional effort (eg tests will fail because
        // a message was changed / an additional one was added / ...). Because logging should NEVER affect the
        // functionality of the code, this is completely unnecessary. Where this leads can be perfectly seen in all
        // moodle code: Things work or do not work and there is no feedback on that. Often things return null if successfully
        // and if nothing happened (also categorized as successful), but no feedback is available which of both cases happened.
        // Users and devs very often can't know why something does not work.
        // If something went wrong either the code should handle the problem or it should throw an exception.
        $this->resetDebugging();
    }

    public function test_create_dsl_score_with_wrong_course_format() {
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('local_adler/course_module_format_not_valid');

        new dsl_score($this->module_db_format, $this->user->id);
    }

    public function test_user_not_enrolled_in_course() {
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('local_adler/user_not_enrolled');

        new dsl_score($this->module, $this->user->id);
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
        $this->assertEquals($this->score_item_primitive->score_max, $dsl_score->get_score());
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

    /** h5p attempt generator is not calculating the scaled attribute.
     * When accessing h5pactivity_attempts it's not using the rawscore field,
     * but instead calculates the scaled value (maxscore * scaled), making this field required for tests.
     * This method works around this issue by calculating the redundant "scaled" field for all existing attempts.
     *
     * Note that this method does not set/update gradebook entries.
     */
    private function fix_scaled_attribute_of_h5pactivity_attempts() {
        global $DB;

        $attempts = $DB->get_records('h5pactivity_attempts');
        foreach ($attempts as $attempt) {
            $attempt->scaled = $attempt->rawscore / $attempt->maxscore;
            $DB->update_record('h5pactivity_attempts', $attempt);
        }
    }

    /**
     * @medium
     */
    public function test_get_score_for_h5p_learning_element() {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        // enroll user in course and set current user (required by h5p generator)
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'student');

        // Create h5p activity
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_h5pactivity');
        $cm = $generator->create_instance(array(
            'course' => $this->course->id,
        ));
        $cm_other_format = get_fast_modinfo($this->course->id, 0, false)->get_cm($cm->cmid);

        // Create score (dsl) item.
        $this->score_item_h5p = $this->getDataGenerator()
            ->get_plugin_generator('local_adler')
            ->create_dsl_score_item($cm_other_format->id);

        // Create dsl_score object.
        $dsl_score = new dsl_score($cm_other_format, $this->user->id);

        // create grader
        $grader = new grader($cm);


        // test no attempt
        $this->assertEquals($this->score_item_h5p->score_min, $dsl_score->get_score());


        // array with test data for attempts with different maxscores and rawscores
        $test_data = [
            ['maxscore' => 100, 'rawscore' => 0, 'expected_score' => 0],
            ['maxscore' => 100, 'rawscore' => 100, 'expected_score' => 100],
            ['maxscore' => 100, 'rawscore' => 50, 'expected_score' => 50],
            ['maxscore' => 50, 'rawscore' => 0, 'expected_score' => 0],
            ['maxscore' => 50, 'rawscore' => 50, 'expected_score' => 100],
            ['maxscore' => 50, 'rawscore' => 25, 'expected_score' => 50],
            ['maxscore' => 200, 'rawscore' => 0, 'expected_score' => 0],
            ['maxscore' => 200, 'rawscore' => 200, 'expected_score' => 100],
            ['maxscore' => 200, 'rawscore' => 100, 'expected_score' => 50],
        ];
        // add more test_data with other expected_score
        for ($i = 0; $i < 25; $i++) {
            $maxscore = rand(1, 1000);
            $rawscore = rand(0, $maxscore);
            $expected_score = $rawscore / $maxscore * $this->score_item_primitive->score_max;
            $test_data[] = ['maxscore' => $maxscore, 'rawscore' => $rawscore, 'expected_score' => $expected_score];
        }

        // test attempts with different maxscores and rawscores
        foreach ($test_data as $data) {
            // Create h5p attempt
            $params = [
                'h5pactivityid' => $cm->id,
                'userid' => $this->user->id,
                'rawscore' => $data['rawscore'],
                'maxscore' => $data['maxscore']
            ];
            $generator->create_attempt($params);
            $this->fix_scaled_attribute_of_h5pactivity_attempts();

            // Create grade entry (grade_grades)
            $grader->update_grades();

            // check result
            $this->assertEquals(round($data['expected_score'],3), round($dsl_score->get_score(), 3));
        }
    }


}
