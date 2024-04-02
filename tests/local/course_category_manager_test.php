<?php

namespace local_adler\local;

use context_coursecat;
use local_adler\lib\adler_testcase;
use local_adler\local\db\moodle_core_repository;
use local_adler\moodle_core;
use Mockery;
use moodle_exception;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

/**
 * @runTestsInSeparateProcesses
 */
class course_category_manager_test extends adler_testcase {
    private $mockRepo;

    public function setUp(): void {
        $this->mockRepo = Mockery::mock('overload:' . moodle_core_repository::class);
    }

    public function test_username_doesnt_exist() {
        // Arrange
        $this->mockRepo->shouldReceive('get_user_id_by_username')->andReturn(false);

        // Act and Assert
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('user_not_found');
        course_category_manager::create_category_user_can_create_courses_in('non_existing_username', 'role', 'category_path');
    }

    public function test_role_doesnt_exist() {
        // Arrange
        $this->mockRepo->shouldReceive('get_user_id_by_username')->andReturn(1); // return a valid user ID
        $this->mockRepo->shouldReceive('get_role_id_by_shortname')->andReturn(false);

        // Act and Assert
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('role_not_found');
        course_category_manager::create_category_user_can_create_courses_in('username', 'non_existing_role', 'category_path');
    }

    public function test_category_already_exists() {
        // Arrange
        $this->mockRepo->shouldReceive('get_user_id_by_username')->andReturn(1); // return a valid user ID
        $this->mockRepo->shouldReceive('get_role_id_by_shortname')->andReturn(1); // return a valid role ID

        $mockPath = Mockery::mock('overload:local_adler\local\course_category_path');
        $mockPath->shouldReceive('exists')->andReturn(true);

        // Act and Assert
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('category_already_exists');
        course_category_manager::create_category_user_can_create_courses_in('valid_username', 'valid_role', 'existing_category_path');
    }

    /**
     * @dataProvider providerTestValidUsernameRoleAndCategoryPath
     */
    public function test_valid_username_role_and_category_path($category_path, $expected_result) {
        // Arrange
        $this->mockRepo->shouldReceive('get_user_id_by_username')->andReturn(1); // return a valid user ID
        $this->mockRepo->shouldReceive('get_role_id_by_shortname')->andReturn(1); // return a valid role ID

        $mockPath = Mockery::mock('overload:' . course_category_path::class);
        $mockPath->shouldReceive('exists')->andReturn(false);
        $mockPath->shouldReceive('create')->andReturn(42);

        $moodle_core_mock = Mockery::mock('alias:' . moodle_core::class)
            ->shouldReceive('context_coursecat_instance')
            ->andReturn(Mockery::mock(context_coursecat::class));

        $moodle_core_mock
            ->shouldReceive('role_assign')
            ->withArgs([1, 1, null])
            ->andReturn(true);

        // Act
        $result = course_category_manager::create_category_user_can_create_courses_in('valid_username', 'valid_role', $category_path);

        // Assert
        $this->assertEquals($expected_result, $result);
    }

    public function providerTestValidUsernameRoleAndCategoryPath() {
        return [
            'null category path' => [null, 42],
            'empty category path' => ['', 42],
            'non-existing category path' => ['non_existing_category_path', 42]
        ];
    }
}