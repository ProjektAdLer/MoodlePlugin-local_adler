<?php

namespace local_adler;

global $CFG;
require_once($CFG->dirroot . '/local/adler/vendor/autoload.php');


use local_adler\local\section\section;
use Mockery;
use PHPUnit\Framework\TestCase;


class plugin_interface_test extends TestCase {
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
}
