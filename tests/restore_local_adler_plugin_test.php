<?php

namespace local_adler;

use ArrayIterator;
use restore_activity_task;
use restore_local_adler_plugin;
use restore_module_structure_step;

defined('MOODLE_INTERNAL') || die();


/**
 * PHPunit test for class restore_local_adler_plugin
 */
class restore_local_adler_plugin_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        // generate array of 3 entries with test data not as loop
        $this->data = [
            (object)[
                "type" => "score",
                "score_min" => "0.0",
                "score_max" => "100.0",
                "timecreated" => "0",
                "timemodified" => "0"
            ],
            (object)[
                "type" => "whatever",
                "score_min" => "5.0",
                "score_max" => "10.0",
                "timecreated" => "123465789",
                "timemodified" => "123456789"
            ],
            (object)[
                "type" => "another_rating",
                "score_min" => "0.0",
                "score_max" => "1.0",
                "timecreated" => "1",
                "timemodified" => "2"
            ]
        ];


        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/local/adler/backup/moodle2/restore_local_adler_plugin.class.php');

        // stub the get_task() method to return a mock task object
        $stub_task = $this->getMockBuilder(restore_activity_task::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_moduleid'])
            ->getMockForAbstractClass();
        $stub_task->method('get_moduleid')
            ->willReturn(1);
        // create stub for restore_module_structure_step
        $this->stub = $this->createMock(restore_module_structure_step::class);
        $this->stub->method('get_task')
            ->willReturn($stub_task);

        // cleanup after every test
        $this->resetAfterTest(true);
    }

    public function test_process_score_item_one_element() {
        // setup
        global $DB;

        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $this->stub);
        $plugin->process_score_item($this->data[0]);


        // verify that the database contains a record
        $this->assertEquals(1, $DB->count_records('local_adler_scores_items'));

        // get the record from the database
        $db_record = $DB->get_records('local_adler_scores_items');
        $db_record = (new ArrayIterator($db_record))->current();
        // verify that the record has the correct values
        $this->assertEquals($this->data[0]->type, $db_record->type);
        $this->assertEquals((float)$this->data[0]->score_min, $db_record->score_min);
        $this->assertEquals((float)$this->data[0]->score_max, $db_record->score_max);
        $this->assertEquals($this->data[0]->timecreated, $db_record->timecreated);
        $this->assertEquals($this->data[0]->timemodified, $db_record->timemodified);
    }

    public function test_process_score_item_multiple_elements() {
        // setup
        global $DB;

        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $this->stub);
        foreach ($this->data as $data) {
            $plugin->process_score_item($data);
        }

        // verify that the database contains a record
        $this->assertEquals(count($this->data), $DB->count_records('local_adler_scores_items'));

        // get the record from the database
        $db_records = $DB->get_records('local_adler_scores_items');
        // check every entry in $db_records
        for ($i = 0; $i < count($this->data); $i++) {
            $db_record = (new ArrayIterator($db_records))->current();
            // verify that the record has the correct values
            $this->assertEquals($this->data[$i]->type, $db_record->type);
            $this->assertEquals((float)$this->data[$i]->score_min, $db_record->score_min);
            $this->assertEquals((float)$this->data[$i]->score_max, $db_record->score_max);
            $this->assertEquals($this->data[$i]->timecreated, $db_record->timecreated);
            $this->assertEquals($this->data[$i]->timemodified, $db_record->timemodified);
            // remove the current record from the array
            array_shift($db_records);
        }
    }

    public function test_process_score_item_invalid_datatype() {
        // setup
        global $DB;

        // create invalid data
        $invalid_data = (object)[
            "type" => "score",
            "score_min" => "null",
            "score_max" => "hundert",
            "timecreated" => "0",
            "timemodified" => "0"
        ];

        $this->expectException(\dml_write_exception::class);

        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $this->stub);
        $plugin->process_score_item($invalid_data);

        // verify that the database contains no records
        $this->assertEquals(0, $DB->count_records('local_adler_scores_items'));
    }

    public function test_process_score_item_missing_fields() {
        // setup
        global $DB;

        // create invalid data
        $invalid_data = (object)[
            "type" => "score",
        ];

        $this->expectException(\dml_write_exception::class);

        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $this->stub);
        $plugin->process_score_item($invalid_data);

        // verify that the database contains no records
        $this->assertEquals(0, $DB->count_records('local_adler_scores_items'));
    }
}