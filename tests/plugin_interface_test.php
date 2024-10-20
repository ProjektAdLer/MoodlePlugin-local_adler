<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler;


global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


use core\di;
use local_adler\lib\adler_testcase;
use local_adler\local\course_category_manager;
use local_adler\local\db\moodle_core_repository;
use local_adler\local\section\section;
use Mockery;


class plugin_interface_test extends adler_testcase {
    /**
     * @runInSeparateProcess
     *
     * # ANF-ID: [MVP12, MVP13]
     */
    public function test_is_section_completed() {
        $user_id = 9;
        $section_id = 7;

        $section_mock = Mockery::mock('overload:'. section::class)->makePartial();
        $section_mock->shouldReceive($section_id);
        $section_mock->shouldReceive('is_completed')
            ->once()
            ->with($user_id)
            ->andReturn(false);

        $systemUnderTest = new plugin_interface();

        $result = $systemUnderTest->is_section_completed($section_id, $user_id);

        $this->assertFalse($result);
    }

    public function test_get_section_name() {
        $section_db_mock = Mockery::mock(moodle_core_repository::class);
        $section_db_mock->shouldReceive('get_moodle_section')
            ->once()
            ->with(7)
            ->andReturn((object) ['name' => 'test_section_name']);

        // inject mock
        di::set(moodle_core_repository::class, $section_db_mock);

        $result = plugin_interface::get_section_name(7);

        $this->assertEquals('test_section_name', $result);
    }

    public function test_create_category_user_can_create_courses_in() {
        // Mock the course_category_manager
        $courseCategoryManagerMock = Mockery::mock(course_category_manager::class);
        $courseCategoryManagerMock
            ->shouldReceive('create_category_user_can_create_courses_in')
            ->andReturn(123); // Assuming 123 is the ID of the created category

        // Set the mock in the DI container
        di::set(course_category_manager::class, $courseCategoryManagerMock);

        // Call the method and assert the result
        $result = plugin_interface::create_category_user_can_create_courses_in('testuser', 'testrole', 'test/category/path');
        $this->assertEquals(123, $result);
    }

}
