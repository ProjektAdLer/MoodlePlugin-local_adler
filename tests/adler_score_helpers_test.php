<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler;

global $CFG;

use local_adler\lib\adler_testcase;
use local_adler\local\exceptions\not_an_adler_cm_exception;
use Mockery;
use moodle_exception;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


class adler_score_helpers_test extends adler_testcase {
    /**
     * @runInSeparateProcess
     * # ANF-ID: [MVP9, MVP8, MVP7]
     */
    public function test_get_adler_score_objects() {
        // create course
        $course = $this->getDataGenerator()->create_course();
        // create 3 cms as array
        for ($i = 0; $i < 3; $i++) {
            $cmids[] = $this->getDataGenerator()->create_module('url', ['course' => $course->id])->cmid;
        }

        $adler_score_helpers_adler_score_mock = Mockery::mock('overload:' . adler_score::class);
        $adler_score_helpers_adler_score_mock
            ->shouldReceive('__construct')
            ->andReturnUsing(function () {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 3) {
                    throw new not_an_adler_cm_exception();
                }
                return (object)[];
            });

        // call function
        $result = adler_score_helpers::get_adler_score_objects($cmids);

        // check result
        $this->assertEquals(3, count($result));
        // check types of result
        $this->assertTrue($result[$cmids[0]] instanceof adler_score);
        $this->assertTrue($result[$cmids[1]] instanceof adler_score);
        $this->assertTrue($result[$cmids[2]] === false);
    }

    /**
     * @runInSeparateProcess
     * # ANF-ID: [MVP9, MVP8, MVP7]
     */
    public function test_get_adler_score_objects_with_moodle_exception() {
        // create course
        $course = $this->getDataGenerator()->create_course();
        // create cm
        $cmid = $this->getDataGenerator()->create_module('url', ['course' => $course->id])->cmid;

        $adler_score_helpers_adler_score_mock = Mockery::mock('overload:' . adler_score::class);
        $adler_score_helpers_adler_score_mock
            ->shouldReceive('__construct')
            ->andThrow(new moodle_exception('blub'));

        // call function and expect exception
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('blub');

        adler_score_helpers::get_adler_score_objects([$cmid]);
    }
}