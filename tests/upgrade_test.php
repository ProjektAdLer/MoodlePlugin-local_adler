<?php /** @noinspection PhpIllegalPsrClassPathInspection */

global $CFG;

use local_adler\lib\adler_testcase;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');
require_once($CFG->dirroot . '/local/adler/db/upgrade.php');

class upgrade_test extends adler_testcase {

    private function create_legacy_module(int $course_id): stdClass {
        return $this->getDataGenerator()->get_plugin_generator('mod_url')->create_instance([
            'course' => $course_id,
            'completion' => COMPLETION_TRACKING_MANUAL,
            'completeionview' => 0,
            'completionpassgrade' => 0
        ]);
    }
    public function test_upgrade_2024090900_completion_changes() {
        // Create 3 courses
        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $course3 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object($course1->id);
        $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object($course3->id);

        $course1_mod1 = $this->create_legacy_module($course1->id);
        $course2_mod1 = $this->create_legacy_module($course2->id);
        $course_3_mod1 = $this->create_legacy_module($course3->id);

        // mock global function upgrade_plugin_savepoint, required by upgrade.php/xmldb_local_adler_upgrade
        function upgrade_plugin_savepoint($v1, $v2, $v3, $v4) {}

        // call cud
        xmldb_local_adler_upgrade(2024090800);

        // verify
        $cm1 = get_fast_modinfo($course1->id)->get_cm($course1_mod1->cmid);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $cm1->completion);
        $cm2 = get_fast_modinfo($course2->id)->get_cm($course2_mod1->cmid);
        $this->assertEquals(COMPLETION_TRACKING_MANUAL, $cm2->completion);
        $cm3 = get_fast_modinfo($course3->id)->get_cm($course_3_mod1->cmid);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $cm3->completion);
    }
}