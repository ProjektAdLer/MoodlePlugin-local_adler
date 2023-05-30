<?php
// When using namespaces, the namespace of the test class should match the namespace of the code under test
// -> no namespace for this test as backup/restore is not namespaced
use local_adler\lib\local_adler_testcase;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

/**
 * PHPunit test for class backup_local_adler_plugin
 * This class can't be tested with regular phpunit tests because the class defines an internal data structure of moodle
 * with the help of moodle helper functions.
 * The structure of this object is not under control of this plugin and might be different in different moodle versions.
 * The only way to reliably recreate this object would be using the same code as the original class.
 * Therefore, this test is implemented as an integration test.
 */
class backup_local_adler_plugin_test extends local_adler_testcase {
    public function setUp(): void {
        parent::setUp();

        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create a module.
        $this->module = $this->getDataGenerator()->create_module('url', ['course' => $this->course->id]);
    }

    /** Get parsed xml from backup controller object.
     * @param $bc backup_controller
     * @param $type string type of backup, one of 'module', 'course'
     * @return false|SimpleXMLElement
     */
    private function get_xml_from_backup(backup_controller $bc, string $type='module') {
        // Get the backup file.
        $file = $bc->get_results();
        $file = reset($file);

        // Extract file to temp dir.
        $tempdir = make_request_directory();
        $extracted_files = $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $tempdir);

        // Search for entry of <type>.xml file and get the full path.
        $type_xml = null;
        foreach ($extracted_files as $key => $_) {
            if (strpos($key, $type . '.xml') !== false) {
                $type_xml = $key;
                break;
            }
        }
        $module_xml_path = $tempdir . DIRECTORY_SEPARATOR . $type_xml;

        // Get the backup file contents and parse it.
        $contents = file_get_contents($module_xml_path);
        return simplexml_load_string($contents);
    }

    /** verify actual score items machtes expected score item
     * @param $expected
     * @param $actual
     * @return void
     */
    private function verify_points($expected, $actual) {
        $this->assertEquals($expected->uuid, $actual->uuid);
        $this->assertEquals((float)$expected->score_max, (float)$actual->score_max);
        $this->assertEquals((int)$expected->timecreated, (int)$actual->timecreated);
        $this->assertEquals((int)$expected->timemodified, (int)$actual->timemodified);
    }

    /**
     * Test the backup score logic.
     * @medium
     */
    public function test_backup_score() {
        // Create score item with generator
        $score_item = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module($this->module->cmid);

        // Create a backup of the module.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $this->module->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();
        $bc->destroy();

        // Get xml from backup.
        $xml = $this->get_xml_from_backup($bc);

        // validate xml values
        $this->verify_points($score_item, $xml->plugin_local_adler_module->adler_module);
    }

    /**
     * Test the backup of module without score data.
     * @medium
     */
    public function test_backup_no_score() {
        // Create a backup of the module.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $this->module->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();
        $bc->destroy();

        // Get xml from backup.
        $xml = $this->get_xml_from_backup($bc);

        // validate xml values
        $this->assertFalse(isset($xml->plugin_local_adler_module->score_max), 'score_max should not be in $xml->plugin_local_adler_module');
    }

    /** Test course backup */

    /** verify actual adler course machtes expected course data
     * @param $expected
     * @param $actual
     * @return void
     */
    private function verify_course($expected, $actual) {
        $this->assertEquals($expected->uuid, $actual->uuid);
        $this->assertEquals((int)$expected->timecreated, (int)$actual->timecreated);
        $this->assertEquals((int)$expected->timemodified, (int)$actual->timemodified);
    }

    public function test_backup_course() {
        // Create score item with generator
        $adler_course_object = $this
            ->getDataGenerator()
            ->get_plugin_generator('local_adler')
            ->create_adler_course_object($this->course->id);

        // Create a backup of the course.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $this->module->course,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();
        $bc->destroy();

        // Get xml from backup.
        $xml = $this->get_xml_from_backup($bc, 'course');

        // validate xml values
        $this->verify_course($adler_course_object, $xml->plugin_local_adler_course->adler_course);
    }

    public function test_backup_course_not_adler_course() {
        // Create a backup of the course.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $this->module->course,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();
        $bc->destroy();

        // Get xml from backup.
        $xml = $this->get_xml_from_backup($bc, 'course');

        // validate xml values
        $this->assertFalse(isset($xml->plugin_local_adler_course->score_max), 'score_max should not be in $xml->plugin_local_adler_course');
    }
}