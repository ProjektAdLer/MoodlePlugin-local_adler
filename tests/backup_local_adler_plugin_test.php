<?php

namespace local_adler;

/**
 * PHPunit test for class backup_local_adler_plugin
 * This class can't be tested with regular phpunit tests because the class defines an internal data structure of moodle
 * with the help of moodle helper functions.
 * The structure of this object is not under control of this plugin and might be different in different moodle versions.
 * The only way to reliably recreate this object would be using the same code as the original class.
 * Therefore, this test is implemented as an integration test.
 */
class backup_local_adler_plugin_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // cleanup after every test
        $this->resetAfterTest(true);

        // Don't be strict about output for this test.
        $this->expectOutputRegex('/.*/');

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a module.
        $this->module = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // Create two score items.
        $this->score_items = [(object)[
            'course_modules_id' => $this->module->cmid,
            'type' => 'score',
            'score_min' => 0.0,
            'score_max' => 100.0,
            'timecreated' => 0,
            'timemodified' => 0
        ], (object)[
            'course_modules_id' => $this->module->cmid,
            'type' => 'gold',
            'score_min' => 0.0,
            'score_max' => 42.0,
            'timecreated' => 1999,
            'timemodified' => 2000
        ]];
    }

    /** Get parsed xml from backup controller object.
     * @param $bc \backup_controller
     * @return \$1|false|\SimpleXMLElement
     */
    private function get_xml_from_backup($bc) {
        // Get the backup file.
        $file = $bc->get_results();
        $file = reset($file);

        // Extract file to temp dir.
        $tempdir = make_request_directory();
        $extracted_files = $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $tempdir);

        // Search for entry of module.xml file and get the full path.
        $module_xml = null;
        foreach ($extracted_files as $key => $_) {
            if (strpos($key, 'module.xml') !== false) {
                $module_xml = $key;
                break;
            }
        }
        $module_xml_path = $tempdir . DIRECTORY_SEPARATOR . $module_xml;

        // Get the backup file contents and parse it.
        $contents = file_get_contents($module_xml_path);
        $xml = simplexml_load_string($contents);

        return $xml;
    }

    /** verify actual score items machtes expected score item
     * @param $expected
     * @param $actual
     * @return void
     */
    private function verify_score_item($expected, $actual) {
        $this->assertEquals($expected->type, $actual->type);
        $this->assertEquals((float)$expected->score_min, (float)$actual->score_min);
        $this->assertEquals((float)$expected->score_max, (float)$actual->score_max);
        $this->assertEquals((int)$expected->timecreated, (int)$actual->timecreated);
        $this->assertEquals((int)$expected->timemodified, (int)$actual->timemodified);
    }

    /**
     * Test the backup score logic.
     * @medium
     */
    public function test_backup_score() {
        global $DB;

        // Create a score item.
        $DB->insert_record('local_adler_scores_items', $this->score_items[0]);

        // Create a backup of the module.
        $bc = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $this->module->cmid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();

        // Get xml from backup.
        $xml = $this->get_xml_from_backup($bc);

        // validate xml values
        $this->verify_score_item($this->score_items[0], $xml->plugin_local_adler_module->score_items->score_item);
    }

    /**
     * Test the backup of module without score data.
     * @medium
     */
    public function test_backup_no_score() {
        // Create a backup of the module.
        $bc = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $this->module->cmid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();

        // Get xml from backup.
        $xml = $this->get_xml_from_backup($bc);

        // validate xml values
        $this->assertEmpty($xml->plugin_local_adler_module->score_items->children());
    }

    /**
     * Test the backup of module with multiple score data.
     * @medium
     */
    public function test_backup_multiple_scores() {
        global $DB;

        $DB->insert_records('local_adler_scores_items', $this->score_items);

        // Create a backup of the module.
        $bc = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $this->module->cmid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();

        // Get xml from backup.
        $xml = $this->get_xml_from_backup($bc);

        // validate xml values
        for ($i = 0; $i < 2; $i++) {
            $this->verify_score_item($this->score_items[$i], $xml->plugin_local_adler_module->score_items->score_item[$i]);
        }
    }
}