<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler\external;


use context_module;
use local_adler\adler_score_helpers;
use local_adler\lib\adler_externallib_testcase;
use Mockery;
use ReflectionClass;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


class score_get_course_scores_test extends adler_externallib_testcase {
    public function provide_test_execute_data() {
        return [
            'success' => [
                'element_count' => 3,
            ],
            'require_login_exception' => [
                'element_count' => 0,
            ],
        ];
    }

    /**
     * @dataProvider provide_test_execute_data
     * @runInSeparateProcess
     *
     * # ANF-ID: [MVP7]
     */
    public function test_execute($element_count) {
        $course = $this->getDataGenerator()->create_course();


        // mock context
        $context_mock = Mockery::mock(context_module::class);
        $context_mock->shouldReceive('instance')->andReturn($context_mock);

        $reflected_class = new ReflectionClass(score_get_course_scores::class);
        $property = $reflected_class->getProperty('context_course');
        $property->setAccessible(true);
        $property->setValue(null, $context_mock->mockery_getName());

        // mock validate_context
        $score_get_course_scores_mock = Mockery::mock(score_get_course_scores::class)->makePartial();
        $score_get_course_scores_mock->shouldReceive('validate_context')->andReturn(true);


        // cant mock get_fast_modinfo, so create course with modules & generate get_achieved_scores return value and expected result
        $adler_score_helpers_mock_get_achieved_scores_return = [];
        $expected_result = [];
        for ($i = 0; $i < $element_count; $i++) {
            $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
            $moduels[] = $module;
            $adler_score_helpers_mock_get_achieved_scores_return[$module->id] = $i * 2;
            $expected_result[] = ['moduleid' => $module->id, 'score' => $i * 2];
        }
        // adler score mock
        $adler_score_helpers_mock = Mockery::mock('overload:' . adler_score_helpers::class);
        $adler_score_helpers_mock->shouldReceive('get_achieved_scores')
            ->andReturn($adler_score_helpers_mock_get_achieved_scores_return);


        $result = $score_get_course_scores_mock->execute($course->id);

        // validate return value
        $this->assertEqualsCanonicalizing($expected_result, $result['data']);
    }

    /**
     * # ANF-ID: [MVP7]
     */
    public function test_execute_returns() {
        // this function just returns what get_adler_score_response_multiple_structure returns
        require_once(__DIR__ . '/lib_test.php');
        (new lib_test())->test_get_adler_score_response_multiple_structure(score_get_course_scores::class);
    }
}