<?php

namespace local_adler;

global $CFG;

use local_adler\lib\local_adler_testcase;
use local_adler\lib\static_mock_utilities_trait;
use moodle_exception;
use ReflectionClass;
use Throwable;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class dsl_score_helpers_dsl_score_mock extends dsl_score {
    public function __construct(object $course_module, int $user_id = null) {
    }
}

class dsl_score_helpers_mock extends dsl_score_helpers {
    use static_mock_utilities_trait;

    public static function get_dsl_score_objects(array $module_ids, int $user_id = null): array {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }
}

class dsl_score_helpers_test extends local_adler_testcase {
    public function test_get_dsl_score_objects() {
        // setup
        // set param $dsl_score_class
        $reflected_class = new ReflectionClass(dsl_score_helpers::class);
        $param_dsl_score_class = $reflected_class->getProperty('dsl_score_class');
        $param_dsl_score_class->setAccessible(true);
        $param_dsl_score_class->setValue(dsl_score_helpers_dsl_score_mock::class);

        // create course
        $course = $this->getDataGenerator()->create_course();
        // create 3 cms as array
        for ($i = 0; $i < 3; $i++) {
            $cmids[] = $this->getDataGenerator()->create_module('url', ['course' => $course->id])->cmid;
        }

        // call function
        $result = dsl_score::get_dsl_score_objects($cmids);

        // check result
        $this->assertEquals(3, count($result));
        // check types of result
        $this->assertTrue($result[$cmids[0]] instanceof dsl_score_helpers_dsl_score_mock);
    }

    public function provide_test_get_achieved_scores_data(): array {
        return [
            'default' => [[
                'exception' => null,
                'exception_msg' => null,
                'exception_at_index' => null,
                'expected_result' => [0, 2, 4],
                'expected_exception' => false,
            ]],
            'completion not enabled' => [[
                'exception' => moodle_exception::class,
                'exception_msg' => 'completion_not_enabled',
                'exception_at_index' => 1,
                'expected_result' => [0, false, 4],
                'expected_exception' => false,
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
     */
    public function test_get_achieved_scores($data) {
        // create 3 dsl_score objects and mock get_score
        for ($i = 0; $i < 3; $i++) {
            $dsl_score_objects[] = $this->getMockBuilder(dsl_score_helpers_dsl_score_mock::class)
                ->disableOriginalConstructor()
                ->getMock();
            $dsl_score_objects[$i]->method('get_score')->willReturn((float)$i * 2);
        }

        // setup exception
        if ($data['exception'] !== null) {
            $dsl_score_objects[$data['exception_at_index']]->method('get_score')->willThrowException(
                new $data['exception']($data['exception_msg'])
            );
        }

        // call function
        try {
            $result = dsl_score_helpers::get_achieved_scores(null, null, $dsl_score_objects);
        } catch (Throwable $e) {
            if ($data['expected_exception'] !== false) {
                $this->assertInstanceOf($data['expected_exception'], $e);
                return;
            } else {
                $this->fail('Unexpected exception: ' . $e->getMessage());
            }
        }

        // check result
        $this->assertEquals($data['expected_result'], $result);
    }

    public function test_get_achieved_scores_with_module_ids() {
        // setup
        $module_ids = [1, 2, 3];
        $user_id = 1;
        // create 3 dsl_score objects and mock get_score
        for ($i = 0; $i < 3; $i++) {
            $dsl_score_objects[] = $this->getMockBuilder(dsl_score_helpers_dsl_score_mock::class)
                ->disableOriginalConstructor()
                ->getMock();
            $dsl_score_objects[$i]->method('get_score')->willReturn((float)$i * 2);
        }
        $expected_result = [0, 2, 4];

        // mock get_dsl_score_objects
        dsl_score_helpers_mock::reset_data();
        dsl_score_helpers_mock::set_returns('get_dsl_score_objects', [$dsl_score_objects]);

        // call function
        $result = dsl_score_helpers_mock::get_achieved_scores($module_ids, $user_id);

        // check result
        $this->assertEquals($expected_result, $result);

        // check function call
        $this->assertEquals([$module_ids, $user_id], dsl_score_helpers_mock::get_calls('get_dsl_score_objects')[0]);
    }

    public function test_get_adler_score_record() {
        // create course
        $course = $this->getDataGenerator()->create_course();

        // create cm
        $cm = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // create adler score item
        $adler_score_item = $this->getDataGenerator()->get_plugin_generator('local_adler')->create_dsl_score_item($cm->cmid);

        // call function
        $result = dsl_score_helpers::get_adler_score_record($cm->cmid);

        // check result
        $this->assertEquals($adler_score_item->id, $result->id);
        $this->assertEquals($adler_score_item->cmid, $result->cmid);
        $this->assertEquals($adler_score_item->score_max, $result->score_max);


        // error case
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('not_an_adler_cm');

        // create cm
        $cm = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        // call function
        dsl_score_helpers::get_adler_score_record($cm->cmid);
    }
}