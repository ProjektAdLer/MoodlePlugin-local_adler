<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler;

global $CFG;

use local_adler\lib\adler_testcase;
use local_adler\local\exceptions\not_an_adler_cm_exception;
use Mockery;
use moodle_exception;
use Throwable;

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

    public function provide_test_get_achieved_scores_data(): array {
        return [
            'default' => [[
                'exception' => null,
                'exception_msg' => null,
                'exception_at_index' => null,
                'expected_result' => [0, 2, 4, false],
                'expected_exception' => false,
            ]],
            'completion not enabled' => [[
                'exception' => moodle_exception::class,
                'exception_msg' => 'completion_not_enabled',
                'exception_at_index' => 1,
                'expected_result' => [0, false, 4, false],
                'expected_exception' => moodle_exception::class,
            ]],
            'other moodle exception' => [[
                'exception' => moodle_exception::class,
                'exception_msg' => 'blub',
                'exception_at_index' => 1,
                'expected_result' => null,
                'expected_exception' => moodle_exception::class,
            ]]
        ];
    }

    /**
     * @dataProvider provide_test_get_achieved_scores_data
     *
     *  # ANF-ID: [MVP9, MVP8, MVP7]
     */
    public function test_get_achieved_scores($data) {
        // create 3 adler_score objects and mock get_score_by_completion_state
        for ($i = 0; $i < 3; $i++) {
            $adler_score_objects[] = $this->getMockBuilder(adler_score_helpers_adler_score_mock::class)
                ->disableOriginalConstructor()
                ->getMock();
            $adler_score_objects[$i]->method('get_score_by_completion_state')->willReturn((float)$i * 2);
        }
        $adler_score_objects[] = false;

        // setup exception
        if ($data['exception'] !== null) {
            $adler_score_objects[$data['exception_at_index']]->method('get_score_by_completion_state')->willThrowException(
                new $data['exception']($data['exception_msg'])
            );
        }

        // call function
        try {
            $result = adler_score_helpers::get_achieved_scores(null, null, $adler_score_objects);
        } catch (Throwable $e) {
            if ($data['expected_exception'] !== false) {
                $this->assertInstanceOf($data['expected_exception'], $e);
                if ($data['exception_msg'] !== null)
                    $this->assertStringContainsString($data['exception_msg'], $e->getMessage());
                return;
            }
            $this->fail('Unexpected exception: ' . $e->getMessage());
        }

        // check result
        $this->assertEquals($data['expected_result'], $result);
    }

    /**
     * # ANF-ID: [MVP9, MVP8, MVP7]
     */
    public function test_get_achieved_scores_with_module_ids() {
        // setup
        $module_ids = [1, 2, 3];
        $user_id = 1;
        // create 3 adler_score objects and mock get_score_by_completion_state
        for ($i = 0; $i < 3; $i++) {
            $adler_score_objects[] = $this->getMockBuilder(adler_score_helpers_adler_score_mock::class)
                ->disableOriginalConstructor()
                ->getMock();
            $adler_score_objects[$i]->method('get_score_by_completion_state')->willReturn((float)$i * 2);
        }
        $expected_result = [0, 2, 4];

        // mock get_adler_score_objects
        $adler_score_helpers_mock = Mockery::mock(adler_score_helpers::class)->makePartial();
        $adler_score_helpers_mock
            ->shouldReceive('get_adler_score_objects')
            ->andReturn($adler_score_objects);

        // call function
        $result = $adler_score_helpers_mock::get_achieved_scores($module_ids, $user_id);

        // check result
        $this->assertEquals($expected_result, $result);
    }
}