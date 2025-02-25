<?php /** @noinspection PhpIllegalPsrClassPathInspection */
// When using namespaces, the namespace of the test class should match the namespace of the code under test
// -> no namespace for this test as backup/restore is not namespaced

use local_adler\lib\adler_testcase;
use local_adler\local\db\adler_sections_repository;
use local_adler\local\upgrade\upgrade_3_2_0_to_4_0_0_completionlib;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/local/adler/backup/moodle2/restore_local_adler_plugin.class.php');


class restore_adler_plugin_test extends adler_testcase {
    public function setUpModule(): array {
        // stub the get_task() method to return a mock task object
        $stub_task = $this->getMockBuilder(restore_activity_task::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_moduleid'])
            ->getMockForAbstractClass();
        $stub_task->method('get_moduleid')
            ->willReturn(1);
        // create stub for restore_module_structure_step
        $stub = $this->createMock(restore_module_structure_step::class);
        $stub->method('get_task')
            ->willReturn($stub_task);

        // generate array of 3 test entries
        $data = [
            $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module(1, array(), false),
            $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module(2, array(
                'score_max' => 10.0,
                'timecreated' => 123456789,
                'timemodified' => 123456789
            ), false),
            $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_module(3, array(
                'score_max' => 30.0,
            ), false)
        ];

        return [$data, $stub];
    }

    /**
     * @param $name string Name of method to set as public
     * @return ReflectionMethod
     * @throws ReflectionException
     *
     * # ANF-ID: [MVP2]
     */
    protected static function getMethodAsPublic(string $name): ReflectionMethod {
        $class = new ReflectionClass(restore_local_adler_plugin::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function test_process_adler_module_one_element() {
        // setup
        global $DB;
        list($data, $stub) = $this->setUpModule();

        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $stub);
        $plugin->process_adler_module($data[0]);


        // verify that the database contains a record
        $this->assertEquals(1, $DB->count_records('local_adler_course_modules'));

        // get the record from the database
        $db_record = $DB->get_records('local_adler_course_modules');
        $db_record = (new ArrayIterator($db_record))->current();
        // verify that the record has the correct values
        $this->assertEquals($data[0]->uuid, $db_record->uuid);
        $this->assertEquals((float)$data[0]->score_max, $db_record->score_max);
        $this->assertEquals($data[0]->timecreated, $db_record->timecreated);
        $this->assertEquals($data[0]->timemodified, $db_record->timemodified);
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function test_process_adler_module_one_element_default_values() {
        // test without optional fields (timecreated, timemodified)
        global $DB;
        list($data, $stub) = $this->setUpModule();

        // test data
        unset($data[1]->timecreated);
        unset($data[1]->timemodified);

        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $stub);
        $plugin->process_adler_module($data[1]);

        // verify that the database contains a record
        $this->assertEquals(1, $DB->count_records('local_adler_course_modules'));

        // verify timecreated and timemodified
        $db_record = $DB->get_records('local_adler_course_modules');
        $db_record = (new ArrayIterator($db_record))->current();
        $this->assertTrue($db_record->timecreated > 0 && $db_record->timecreated <= time());
        $this->assertTrue($db_record->timemodified > 0 && $db_record->timemodified <= time());
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function test_process_adler_module_multiple_elements() {
        list($data, $stub) = $this->setUpModule();

        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $stub);
        $plugin->process_adler_module($data[0]);
        $this->expectException(dml_write_exception::class);
        $plugin->process_adler_module($data[1]);
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function test_process_adler_module_invalid_datatype() {
        // setup
        global $DB;
        list($data, $stub) = $this->setUpModule();

        // create invalid data
        $invalid_data = (object)[
            "score_max" => "hundert",
            "timecreated" => "0",
            "timemodified" => "0"
        ];

        $exception_thrown = false;
        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $stub);
        try {
            $plugin->process_adler_module($invalid_data);
        } catch (dml_write_exception $e) {
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown, "Exception was not thrown");

        // verify that the database contains no records
        $this->assertEquals(0, $DB->count_records('local_adler_course_modules'));
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function test_process_adler_module_missing_fields() {
        // setup
        global $DB;
        list($data, $stub) = $this->setUpModule();

        // create invalid data
        $invalid_data = (object)[];

        $exception_thrown = false;
        // call the method to test
        $plugin = new restore_local_adler_plugin('local', 'adler', $stub);
        try {
            $plugin->process_adler_module($invalid_data);
        } catch (dml_write_exception $e) {
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown, "Exception was not thrown");

        // verify that the database contains no records
        $this->assertEquals(0, $DB->count_records('local_adler_course_modules'));
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function test_define_module_plugin_structure() {
        list($data, $stub) = $this->setUpModule();

        // create mock for restore_path_element
        $mock_path_element = $this->getMockBuilder(restore_path_element::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_path'])
            ->getMockForAbstractClass();
        $mock_path_element->method('get_path')->willReturn('module');

        // create plugin object
        $plugin = new restore_local_adler_plugin('local', 'adler', $stub);
        $property = new ReflectionProperty(restore_local_adler_plugin::class, 'connectionpoint');
        $property->setAccessible(true);
        $property->setValue($plugin, $mock_path_element);
        $method = self::getMethodAsPublic('define_module_plugin_structure');

        // test
        $paths = $method->invoke($plugin);

        // verify
        $this->assertCount(1, $paths);
        $this->assertEquals('adler_module', $paths[0]->get_name());
        $this->assertStringContainsString('adler_module', $paths[0]->get_path());
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function setUpCourse(): array {
        $data = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object(1, [], false);

        $stub_task = $this->getMockBuilder(restore_course_task::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_courseid'])
            ->getMockForAbstractClass();
        $stub_task->method('get_courseid')
            ->willReturn(7);
        $stub = $this->createMock(restore_course_structure_step::class);
        $stub->method('get_task')
            ->willReturn($stub_task);

        return [$data, $stub];
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function test_define_course_plugin_structure() {
        list($data, $stub) = $this->setUpCourse();

        // create mock for restore_path_element
        $mock_path_element = $this->getMockBuilder(restore_path_element::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_path'])
            ->getMockForAbstractClass();
        $mock_path_element->method('get_path')->willReturn('course');

        // create plugin object
        $plugin = new restore_local_adler_plugin('local', 'adler', $stub);
        $property = new ReflectionProperty(restore_local_adler_plugin::class, 'connectionpoint');
        $property->setAccessible(true);
        $property->setValue($plugin, $mock_path_element);
        $method = self::getMethodAsPublic('define_course_plugin_structure');

        // test
        $paths = $method->invoke($plugin);

        // verify
        $this->assertCount(2, $paths);
        $this->assertEquals('plugin_release_set_version', $paths[0]->get_name());
        $this->assertEquals('adler_course', $paths[1]->get_name());
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function test_process_adler_course() {
        // setup
        global $DB;
        list($data, $stub) = $this->setUpCourse();

        // create test object
        $plugin = new restore_local_adler_plugin('local', 'adler', $stub);

        // call the method to test
        $plugin->process_adler_course($data);

        // get the record from the database
        $db_record = $DB->get_records('local_adler_course');
        $db_record = array_pop($db_record);
        // verify that the database contains a record
        $this->assertEquals(1, $DB->count_records('local_adler_course'));
        $this->assertEquals(7, $db_record->course_id);
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function test_process_adler_course_optional_fields() {
        // setup
        global $DB;
        list($data, $stub) = $this->setUpCourse();

        // remove optional fields
        unset($data->timecreated);
        unset($data->timemodified);

        // create test object
        $plugin = new restore_local_adler_plugin('local', 'adler', $stub);

        // call the method to test
        $plugin->process_adler_course($data);

        // get the record from the database
        $db_record = $DB->get_records('local_adler_course');
        $db_record = array_pop($db_record);
        // verify that the database contains a record
        $this->assertEquals(1, $DB->count_records('local_adler_course'));
        $this->assertTrue($db_record->timecreated > 0 && $db_record->timecreated <= time());
        $this->assertTrue($db_record->timemodified > 0 && $db_record->timemodified <= time());
    }

    /**
     * # ANF-ID: [MVP2]
     */
    public function test_define_section_plugin_structure() {
        $restore_mock = $this
            ->getMockBuilder(restore_local_adler_plugin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_pathfor'])
            ->getMock();
        $restore_mock
            ->method('get_pathfor')
            ->with('/adler_section')
            ->willReturn('blub');

        // make define_section_plugin_structure() public
        $class = new ReflectionClass($restore_mock);
        $method = $class->getMethod('define_section_plugin_structure');
        $method->setAccessible(true);

        $res = $method->invoke($restore_mock);

        $this->assertEquals('blub', $res[0]->get_path());
    }

    public static function provide_test_process_adler_section_data() {
        return [
            'full object' => [
                'restore_data' => (object)[
                    'required_points_to_complete' => 1,
                    'timecreated' => 1,
                    'timemodified' => 1,
                ],
            ],
            'without optional fields' => [
                'restore_data' => (object)[
                    'required_points_to_complete' => 1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider provide_test_process_adler_section_data
     *
     * # ANF-ID: [MVP2]
     */
    public function test_process_adler_section($restore_data) {
        $restore_mock = $this
            ->getMockBuilder(restore_local_adler_plugin::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $repository_mock = Mockery::mock(adler_sections_repository::class);
        $task_mock = $this->getMockBuilder(restore_section_task::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_sectionid'])
            ->getMockForAbstractClass();

        $repository_mock->shouldReceive('create_adler_section')
            ->once()
            ->withArgs(function($data) use ($restore_data) {
                // verify repository call
                $this->assertEquals(1, $data->section_id);
                $this->assertEquals($restore_data->required_points_to_complete, $data->required_points_to_complete);
                if (property_exists($restore_data, 'timecreated')) {
                    $this->assertEquals($restore_data->timecreated, $data->timecreated);
                } else {
                    $this->assertTrue($data->timecreated > 0 && $data->timecreated <= time());
                }
                return true;
            });

        $task_mock->method('get_sectionid')
            ->willReturn(1);

        // set the repository and task properties
        $class = new ReflectionClass($restore_mock);
        $property = $class->getProperty('adler_sections_repository');
        $property->setAccessible(true);
        $property->setValue($restore_mock, $repository_mock);
        $property = $class->getProperty('task');
        $property->setAccessible(true);
        $property->setValue($restore_mock, $task_mock);

        // call the method to test
        $restore_mock->process_adler_section($restore_data);
    }

    public static function provide_test_call_upgrade_3_2_0_to_4_0_0_completionlib_data() {
        return [
            'legacy course' => ['version' => null],
            'new course' => ['version' => '4.0.0'],
            'invalid course' => ['version' => '1.1.0'],
        ];
    }

    /**
     * @dataProvider provide_test_call_upgrade_3_2_0_to_4_0_0_completionlib_data
     * @runInSeparateProcess
     */
    public function test_call_upgrade_3_2_0_to_4_0_0_completionlib($version) {
        // setup mock
        $upgrade_mock = Mockery::spy('overload:' . upgrade_3_2_0_to_4_0_0_completionlib::class);
        if ($version === null) {
            $upgrade_mock->shouldReceive('execute')->once();
        } else {
            $upgrade_mock->shouldReceive('execute')->never();
        }

        if ($version === '1.1.0') {
            $this->expectException(moodle_exception::class);
            $this->expectExceptionMessage('invalid_plugin_release_set_version');
        }

        // setup data
        list($data, $stub) = $this->setUpCourse();
        if ($version !== null) {
            $data->plugin_release_set_version = $version;
        }

        // create test object
        $plugin = new restore_local_adler_plugin('local', 'adler', $stub);

        // call the method to test
        $plugin->process_plugin_release_set_version($data);
        $plugin->after_restore_course();
    }
}