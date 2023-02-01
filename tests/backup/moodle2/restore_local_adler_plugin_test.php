<?php
// When using namespaces, the namespace of the test class should match the namespace of the code under test
// -> no namespace for this test as backup/restore is not namespaced

use local_adler\lib\local_adler_testcase;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

/**
 * PHPunit test for class restore_local_adler_plugin
 */
class restore_local_adler_plugin_test extends local_adler_testcase {
    public function setUp(): void {
        parent::setUp();

        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/local/adler/backup/moodle2/restore_local_adler_plugin.class.php');


        // generate array of 3 test entries
        $this->data = [
            $this->getDataGenerator()->get_plugin_generator('local_adler')->create_dsl_score_item(1, array(), false),
            $this->getDataGenerator()->get_plugin_generator('local_adler')->create_dsl_score_item(2, array(
                'score_max' => 10.0,
                'timecreated' => 123456789,
                'timemodified' => 123456789
            ), false),
            $this->getDataGenerator()->get_plugin_generator('local_adler')->create_dsl_score_item(3, array(
                'score_max' => 30.0,
            ), false)
        ];


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
    }

    /**
     * @param $name string Name of method to set as public
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected static function getMethodAsPublic(string $name): ReflectionMethod {
        $class = new ReflectionClass(restore_local_adler_plugin::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function test_process_points_one_element() {
        // setup
        global $DB;

        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $this->stub);
        $plugin->process_points($this->data[0]);


        // verify that the database contains a record
        $this->assertEquals(1, $DB->count_records('local_adler_scores_items'));

        // get the record from the database
        $db_record = $DB->get_records('local_adler_scores_items');
        $db_record = (new ArrayIterator($db_record))->current();
        // verify that the record has the correct values
        $this->assertEquals((float)$this->data[0]->score_max, $db_record->score_max);
        $this->assertEquals($this->data[0]->timecreated, $db_record->timecreated);
        $this->assertEquals($this->data[0]->timemodified, $db_record->timemodified);
    }

    public function test_process_points_multiple_elements() {
        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $this->stub);
        $plugin->process_points($this->data[0]);
        $this->expectException(dml_write_exception::class);
        $plugin->process_points($this->data[1]);
    }

    public function test_process_points_invalid_datatype() {
        // setup
        global $DB;

        // create invalid data
        $invalid_data = (object)[
            "score_max" => "hundert",
            "timecreated" => "0",
            "timemodified" => "0"
        ];

        $exception_thrown = false;
        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $this->stub);
        try {
            $plugin->process_points($invalid_data);
        } catch (dml_write_exception $e) {
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown, "Exception was not thrown");

        // verify that the database contains no records
        $this->assertEquals(0, $DB->count_records('local_adler_scores_items'));
    }

    public function test_process_points_missing_fields() {
        // setup
        global $DB;

        // create invalid data
        $invalid_data = (object)[

        ];

        $exception_thrown = false;
        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $this->stub);
        try {
            $plugin->process_points($invalid_data);
        } catch (dml_write_exception $e) {
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown, "Exception was not thrown");

        // verify that the database contains no records
        $this->assertEquals(0, $DB->count_records('local_adler_scores_items'));
    }

    /** Test define_module_plugin_structure() */
    public function test_define_module_plugin_structure() {
        // setup
        // create mock for restore_path_element
        $mock_path_element = $this->getMockBuilder(restore_path_element::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_path'])
            ->getMockForAbstractClass();
        $mock_path_element->method('get_path')->willReturn('module');

        // create plugin object
        $plugin = new restore_local_adler_plugin('local', 'adler', $this->stub);
        $property = new ReflectionProperty(restore_local_adler_plugin::class, 'connectionpoint');
        $property->setValue($plugin, $mock_path_element);
        $method = self::getMethodAsPublic('define_module_plugin_structure');

        // test
        $paths = $method->invoke($plugin);

        // verify
        $this->assertCount(1, $paths);
        $this->assertEquals('points', $paths[0]->get_name());
        $this->assertStringContainsString('points', $paths[0]->get_path());
    }
}