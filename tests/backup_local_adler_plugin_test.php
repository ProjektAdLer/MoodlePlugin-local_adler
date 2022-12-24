<?php

namespace local_adler;

use ArrayIterator;

/**
 * PHPunit test for class backup_local_adler_plugin
 * This class can't be tested with regular phpunit tests because the class defines an internal data structure
 * with the help of moodle helper functions.
 * The structure of this object is not under control of this plugin and might be different in different moodle versions.
 * The only way to reliably recreate this object would be using the same code as the original class.
 * Therefore, this test is implemented as an integration test.
 */
class backup_local_adler_plugin_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        // cleanup after every test
        $this->resetAfterTest(true);
    }

    /**
     * Test the backup score logic.
     * @medium
     */
    public function test_backup_score() {
        // Don't be strict about output for this test.
        $this->expectOutputRegex('/.*/');

        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        global $DB;

        $this->resetAfterTest(true);          // reset all changes automatically after this test

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a module.
        $module = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // Create a score item.
        $score_item = (object)[
            'course_modules_id' => $module->cmid,
            'type' => 'score',
            'score_min' => 0.0,
            'score_max' => 100.0,
            'timecreated' => 0,
            'timemodified' => 0
        ];
        $DB->insert_record('local_adler_scores_items', $score_item);

        // Create a backup of the module.
        $bc = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $module->cmid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();

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

        // validate xml values
        $this->assertEquals($score_item->type, (string)$xml->plugin_local_adler_module->score_items->score_item->type);
        $this->assertEquals($score_item->score_min, (float)$xml->plugin_local_adler_module->score_items->score_item->score_min);
        $this->assertEquals($score_item->score_max, (float)$xml->plugin_local_adler_module->score_items->score_item->score_max);
        $this->assertEquals($score_item->timecreated, (int)$xml->plugin_local_adler_module->score_items->score_item->timecreated);
        $this->assertEquals($score_item->timemodified, (int)$xml->plugin_local_adler_module->score_items->score_item->timemodified);
    }
}