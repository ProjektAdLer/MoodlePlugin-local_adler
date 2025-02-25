<?php /** @noinspection PhpIllegalPsrClassPathInspection */

global $CFG;

use core_completion\cm_completion_details;
use local_adler\lib\adler_testcase;
use local_adler\local\exceptions\not_an_adler_course_exception;
use local_adler\local\upgrade\upgrade_3_2_0_to_4_0_0_completionlib;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class upgrade_3_2_0_to_4_0_0_completionlib_test extends adler_testcase {
    public static function provide_execute_simple_learning_element_data(): array {
        return [
            'simple LE' => [
                'module' => 'url'
            ],
            'h5p LE' => [
                'module' => 'h5pactivity'
            ]
        ];
    }

    /**
     * @dataProvider provide_execute_simple_learning_element_data
     */
    public function test_execute(string $module_type) {
        global $DB;

        // create course
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        // make course an adler course
        $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object($course->id);

        // create module
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $module = $this->create_legacy_module($module_type, $course->id);

        $cud = new upgrade_3_2_0_to_4_0_0_completionlib($course->id);
        $cud->execute();

        // check if completion is set to view tracking
        $cm = get_fast_modinfo($course->id)->get_cm($module->cmid);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $cm->completion);
        if ($module_type == 'url') {
            $this->assertEquals(0, $cm->completionpassgrade);
            $this->assertEquals(1, $cm->completionview);
        } else {
            $this->assertEquals(1, $cm->completionpassgrade);
            $this->assertEquals(0, $cm->completionview);
            // check passing grade
            $grade_max = $DB->get_field('grade_items', 'grademax', ['itemmodule' => $module_type, 'iteminstance' => $module->id]);
            $pass_grade = $DB->get_field('grade_items', 'gradepass', ['itemmodule' => $module_type, 'iteminstance' => $module->id]);
            $this->assertEquals($grade_max, $pass_grade);
        }
    }

    public function test_completion_already_auto() {
        // create course
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        // make course an adler course
        $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object($course->id);

        // create module
        $module = $this->getDataGenerator()->create_module('url', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completeionview' => 0,
            'completionpassgrade' => 0
        ]);

        $cud = new upgrade_3_2_0_to_4_0_0_completionlib($course->id);
        $cud->execute();

        // check if completion is set to view tracking
        $cm = get_fast_modinfo($course->id)->get_cm($module->cmid);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $cm->completion);
        $this->assertEquals(0, $cm->completionpassgrade);
        $this->assertEquals(0, $cm->completionview);
    }

    public function test_h5p_element_without_grade() {
        // create course
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        // make course an adler course
        $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object($course->id);

        // create module
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $module = $this->create_legacy_module('h5pactivity', $course->id, true);


        $cud = new upgrade_3_2_0_to_4_0_0_completionlib($course->id);
        $cud->execute();

        $cm = get_fast_modinfo($course->id)->get_cm($module->cmid);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $cm->completion);
        $this->assertEquals(0, $cm->completionpassgrade);
        $this->assertEquals(1, $cm->completionview);
    }

    public function test_execute_not_adler_course() {
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        $this->expectException(not_an_adler_course_exception::class);

        $cud = new upgrade_3_2_0_to_4_0_0_completionlib($course->id);
        $cud->execute();
    }


    /**
     * @param string $module_type
     * @param int $course_id
     * @param bool $grade_none This disables the creation of an entry in the grade_items table
     * @return stdClass
     * @throws coding_exception
     */
    private function create_legacy_module(string $module_type, int $course_id, bool $grade_none = false): stdClass {
        $module_data = [
            'course' => $course_id,
            'completion' => COMPLETION_TRACKING_MANUAL,
            'completeionview' => 0,
            'completionpassgrade' => 0
        ];
        if ($grade_none) {
            $module_data['grade'] = 0;
        }
        return $this->getDataGenerator()->get_plugin_generator('mod_' . $module_type)->create_instance($module_data);
    }

    public function test_with_user_attempt() {
        // create course
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        // make course an adler course
        $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object($course->id);

        // create module
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $module = $this->create_legacy_module('h5pactivity', $course->id);

        // mark as completed for user
        $completion = new completion_info($course);
        $completion->update_state(get_fast_modinfo($course)->get_cm($module->cmid), COMPLETION_COMPLETE, $user->id);
        // verify element is completed for user
        $h5p_completion_state = cm_completion_details::get_instance(
            get_fast_modinfo($course)->get_cm($module->cmid),
            $user->id
        );
        $this->assertTrue($h5p_completion_state->is_overall_complete(), 'Element is not completed before test');

        $cud = new upgrade_3_2_0_to_4_0_0_completionlib($course->id);
        $cud->execute();

        // check if completion is set to view tracking
        $cm = get_fast_modinfo($course->id)->get_cm($module->cmid);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $cm->completion);
        $this->assertEquals(1, $cm->completionpassgrade);
        $this->assertEquals(0, $cm->completionview);

        // verify element is still completed
        $h5p_completion_state = cm_completion_details::get_instance(  // has to be recreated to reflect completion state change
            get_fast_modinfo($course)->get_cm($module->cmid),
            $user->id
        );
        $this->assertTrue($h5p_completion_state->is_overall_complete(), 'Element is not completed after test');

        // create attempt
        $this->create_h5p_attempt($module, $course->id, $user->id);
        // verify element is still completed
        $h5p_completion_state = cm_completion_details::get_instance(  // has to be recreated to reflect completion state change
            get_fast_modinfo($course)->get_cm($module->cmid),
            $user->id
        );
    }

    private function create_h5p_attempt(stdClass $module, int $course_id, int $user_id) {
        global $CFG;
        $grade_item = grade_item::fetch([
            'itemname' => $module->name,
            'gradetype' => GRADE_TYPE_VALUE,
            'courseid' => $course_id
        ]);
        $grade_data_class = new stdClass();
        $grade_data_class->userid = $user_id;
        $grade_data_class->rawgrade = $grade_item->grademax;
        require_once($CFG->dirroot . '/mod/h5pactivity/lib.php');
        h5pactivity_grade_item_update($module, $grade_data_class);
    }
}
