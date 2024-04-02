<?php

namespace local_adler\local;

global $CFG;

use invalid_parameter_exception;
use local_adler\lib\adler_testcase;
use Mockery;
use moodle_exception;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class course_category_path_test extends adler_testcase {
    public function test_constructor_with_null() {
        $path = new course_category_path(null);
        $this->assertEquals(0, count($path));
    }

    public function test_constructor_with_empty_string() {
        $path = new course_category_path('');
        $this->assertEquals(0, count($path));
    }

    public function test_constructor_with_spaces() {
        $path = new course_category_path('category1 / category2');
        $this->assertEquals(2, count($path));
    }

    public function test_constructor_without_spaces() {
        $path = new course_category_path('category1/category2');
        $this->assertEquals(2, count($path));
    }

    public function test_constructor_with_single_element() {
        $path = new course_category_path('category1');
        $this->assertEquals(1, count($path));
    }

    public function test_constructor_with_preceding_and_trailing_slashes() {
        $path = new course_category_path('/category1/category2/');
        $this->assertEquals(2, count($path));
    }

    public function test_constructor_with_spaces_around_slashes() {
        $path = new course_category_path(' category1 / category2 ');
        $this->assertEquals(2, count($path));
    }

    public function test_to_string_method() {
        $path = new course_category_path('category1/category2');
        $this->assertEquals('category1 / category2', $path->__toString());
    }

    public function test_get_path_method() {
        $path = new course_category_path('category1/category2');
        $this->assertEquals(['category1', 'category2'], $path->get_path());
    }

    public function test_count_method() {
        $path = new course_category_path('category1/category2');
        $this->assertEquals(2, $path->count());
    }

    private function setup_make_categories_list_mock() {
        $mock = Mockery::mock('alias:core_course_category');
        $mock->shouldReceive('make_categories_list')->andReturn([
            1 => 'category1 / category2',
            2 => 'category3 / category4',
        ]);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_get_category_id_method_category_exists() {
        $this->setup_make_categories_list_mock();

        $path = new course_category_path('category1/category2');
        $this->assertEquals(1, $path->get_category_id());
    }

    /**
     * @runInSeparateProcess
     */
    public function test_get_category_id_method_category_does_not_exists() {
        $this->setup_make_categories_list_mock();

        $path = new course_category_path('category5/category6');
        $this->expectException(moodle_exception::class);
        $path->get_category_id();
    }

    /**
     * @runInSeparateProcess
     */
    public function test_exists_method_category_exists() {
        $this->setup_make_categories_list_mock();

        $path = new course_category_path('category1/category2');
        $this->assertTrue($path->exists());
    }

    /**
     * @runInSeparateProcess
     */
    public function test_exists_method_category_does_not_exist() {
        $this->setup_make_categories_list_mock();

        $path = new course_category_path('category5/category6');
        $this->assertFalse($path->exists());
    }

    public function test_append_to_path_with_valid_path_part(): void {
        $path = new course_category_path('test/path');
        $path->append_to_path('new_part');
        $this->assertEquals('test / path / new_part', (string)$path);
    }

    public function test_append_to_path_with_empty_path_part(): void {
        $this->expectException(invalid_parameter_exception::class);
        $path = new course_category_path('test/path');
        $path->append_to_path('');
    }


    public function test_append_to_path_with_spaces_around_path_part(): void {
        $path = new course_category_path('test/path');
        $path->append_to_path(' new_part ');
        $this->assertEquals('test / path / new_part', (string)$path);
    }

    public function test_append_to_path_with_multiple_parts_in_path_part(): void {
        $path = new course_category_path('test/path');
        $path->append_to_path('new_part1/new_part2');
        $this->assertEquals('test / path / new_part1 / new_part2', (string)$path);
        $this->assertEquals(4, $path->count());
    }

    public function test_count_zero_exists_false(): void {
        $path = new course_category_path('');
        $this->assertEquals(0, $path->count());
        $this->assertFalse($path->exists());
    }

    public function test_create_with_count_zero(): void {
        $this->expectException(invalid_parameter_exception::class);
        $path = new course_category_path('');
        $path->create();
    }

    public function test_create_with_empty_path(): void {
        $this->expectException(invalid_parameter_exception::class);
        $path = new course_category_path('');
        $path->create();
    }

    /**
     * @runInSeparateProcess
     */
    public function test_create_category_already_exists(): void {
        $this->setup_make_categories_list_mock();

        $path = new course_category_path('category1/category2');
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('category_already_exists');
        $path->create();
    }


    /**
     * @runInSeparateProcess
     */
    public function test_create_with_one_segment(): void {
        $mock = Mockery::mock('alias:core_course_category');
        $mock->shouldReceive('make_categories_list')->andReturn([]);
        $mock->shouldReceive('create')->andReturn((object) ['id' => 1]);

        $path = new course_category_path('segment1');
        $this->assertEquals(1, $path->count());
        $this->assertFalse($path->exists());
        $this->assertIsInt($path->create());
    }

    /**
     * @runInSeparateProcess
     */
    public function test_create_with_two_segments_first_exists(): void {
        $mock = Mockery::mock('alias:core_course_category');
        $mock->shouldReceive('make_categories_list')->andReturn([42 => 'segment1']);
        $mock->shouldReceive('create')->andReturn((object) ['id' => 1]);

        $path = new course_category_path('segment1/segment2');
        $this->assertEquals(2, $path->count());
        $this->assertFalse($path->exists());
        $this->assertIsInt($path->create());
    }

    /**
     * @runInSeparateProcess
     */
    public function test_create_with_two_segments_none_exists(): void {
        $mock = Mockery::mock('alias:core_course_category');
        $mock->shouldReceive('make_categories_list')->andReturn([]);
        $mock->shouldReceive('create')->andReturn((object) ['id' => 1]);

        $path = new course_category_path('segment1/segment2');
        $this->assertEquals(2, $path->count());
        $this->assertFalse($path->exists());
        $this->assertIsInt($path->create());
    }
}