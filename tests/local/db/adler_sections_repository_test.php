<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local\db;

global $CFG;

use component_generator_base;
use core\di;
use dml_exception;
use local_adler\lib\adler_testcase;
use local_adler\local\db\adler_sections_repository;
use local_adler\local\db\moodle_core_repository;
use Mockery;
use moodle_database;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class adler_sections_repository_test extends adler_testcase {
    private component_generator_base $adler_generator;

    public function setUp(): void {
        parent::setUp();
        $this->adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');
    }

    public function provide_test_get_adler_section_by_uuid_data() {
        return [
            'success' => [
                'success' => true,
            ],
            'exception' => [
                'success' => false,
            ]
        ];
    }

    /**
     * @dataProvider provide_test_get_adler_section_by_uuid_data
     *
     * # ANF-ID: [MVP6]
     */
    public function test_get_adler_section_by_uuid($success) {
        // create adler_section entry
        $adler_section = $this->adler_generator->create_adler_section(1);

        // mock section db
        $db_mock = Mockery::mock(moodle_core_repository::class)->makePartial();
        if ($success) {
            $db_mock->shouldReceive('get_moodle_section')->andReturn((object)['course' => 1]);
        } else {
            $db_mock->shouldReceive('get_moodle_section')->andReturn((object)['course' => 2]);

            $this->expectException(dml_exception::class);
        }

        // inject mock
        di::set(moodle_core_repository::class, $db_mock);

        // call function
        $db_adler_section = di::get(adler_sections_repository::class)->get_adler_section_by_uuid($adler_section->uuid, 1);

        // check result
        $this->assertEquals($adler_section, $db_adler_section);
    }

    /**
     * # ANF-ID: [MVP6, MVP12]
     */
    public function test_get_adler_section() {
        // create adler_section entry
        $adler_section = $this->adler_generator->create_adler_section(1);

        $db_adler_section = di::get(adler_sections_repository::class)->get_adler_section(1);

        $this->assertEquals($adler_section, $db_adler_section);
    }

    public function test_get_all_adler_sections() {
        $adler_sections_repository = di::get(adler_sections_repository::class);

        // create section objects
        $section1 = $this->adler_generator->create_adler_section(1);
        $section2 = $this->adler_generator->create_adler_section(2);

        // retrieve records using the method
        $retrieved_sections = $adler_sections_repository->get_all_adler_sections();

        // validate retrieved records
        $this->assertCount(2, $retrieved_sections);
        $this->assertEquals(1, array_values($retrieved_sections)[0]->section_id);
        $this->assertEquals(2, array_values($retrieved_sections)[1]->section_id);
    }

    public function test_delete_adler_section_by_section_id() {
        $adler_sections_repository = di::get(adler_sections_repository::class);

        // create section object
        $section = $this->adler_generator->create_adler_section(1);

        // ensure record exists
        $record = di::get(moodle_database::class)->get_record('local_adler_sections', ['section_id' => 1]);
        $this->assertNotEmpty($record);

        // delete record
        $adler_sections_repository->delete_adler_section_by_section_id(1);

        // ensure record no longer exists
        $record = di::get(moodle_database::class)->get_record('local_adler_sections', ['section_id' => 1]);
        $this->assertEmpty($record);
    }

    public function test_create_adler_section() {
        $adler_sections_repository = di::get(adler_sections_repository::class);

        // create section object
        $section = $this->adler_generator->create_adler_section(1, [], false);

        // insert record
        $id = $adler_sections_repository->create_adler_section($section);

        // ensure record exists
        $record = di::get(moodle_database::class)->get_record('local_adler_sections', ['id' => $id]);
        $this->assertNotEmpty($record);
        $this->assertEquals('1', $record->section_id);
    }
}