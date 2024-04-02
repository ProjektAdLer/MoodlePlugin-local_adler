<?php

namespace local_adler;


global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


use local_adler\lib\adler_testcase;
use local_adler\local\section\section;
use local_adler\local\section\db as section_db;
use Mockery;


class plugin_interface_test extends adler_testcase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_section_name() {
        $section_db_mock = Mockery::mock('overload:'. section_db::class);
        $section_db_mock->shouldReceive('get_moodle_section')
            ->once()
            ->with(7)
            ->andReturn((object) ['name' => 'test_section_name']);

        $result = plugin_interface::get_section_name(7);

        $this->assertEquals('test_section_name', $result);
    }
}
