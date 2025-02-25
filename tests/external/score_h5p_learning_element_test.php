<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler\external;


use context_module;
use core\di;
use local_adler\adler_score_helpers;
use local_adler\lib\adler_externallib_testcase;
use Mockery;
use moodle_exception;
use ReflectionClass;
use invalid_parameter_exception;

defined('MOODLE_INTERNAL') || die();


global $CFG;
require_once(__DIR__ . '/lib_test.php');
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');


/**
 * @preserveGlobalState disabled
 */
class score_h5p_learning_element_test extends adler_externallib_testcase {
    public static function provide_test_get_module_ids_from_xapi_data() {
        return [
            [
                'xapi' => '[{"actor":{"name":"student","mbox":"stu@dent.de","objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":"df8tub15w"},"name":{"en-US":"Anfügen am Ende einer einfach verketteten Liste"}}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.10","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":6,"raw":0,"scaled":0},"completion":true,"duration":"PT0.97S","success":false}}]',
                'expect_exception' => null,
                'contextid_to_instanceid_mapping' => [[41], [1]],
                'expected_result' => [1]
            ], [
                'xapi' => '[{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5},"name":{"en-US":"Metriken Teil 1"},"interactionType":"compound","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":""}}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.InteractiveVideo-1.22","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":13,"raw":7,"scaled":0.5385},"completion":true,"success":false,"duration":"PT86.76S"}},{"actor":{"name":"student","mbox":"stu@dent.de","objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/251","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":"df8tub15w"},"name":{"en-US":"Anfügen am Ende einer einfach verketteten Liste"}}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.10","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":6,"raw":0,"scaled":0},"completion":true,"duration":"PT0.97S","success":false}}]',
                'expect_exception' => null,
                'contextid_to_instanceid_mapping' => [[251, 41], [1, 2]],
                'expected_result' => [1, 2]
            ], [
                'xapi' => '[{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5},"name":{"en-US":"Metriken Teil 1"},"interactionType":"compound","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":""}}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.InteractiveVideo-1.22","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":13,"raw":7,"scaled":0.5385},"completion":true,"success":false,"duration":"PT86.76S"}},{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41?subContentId=d72ecc5d-9f18-4017-aa4e-29f1932de84b","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5,"http://h5p.org/x-api/h5p-subContentId":"d72ecc5d-9f18-4017-aa4e-29f1932de84b"},"name":{"en-US":"Wörter einordnen"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p>Ziehe die Wörter in die richtigen Felder!</p>\n<br/>Eine __________ ist im Software Engineering eine __________ Aussage über ein __________, einen __________, oder __________."},"correctResponsesPattern":["Metrik[,]quantifizierte[,]Artefakt[,]Prozess[,]Projekte"]}},"context":{"contextActivities":{"parent":[{"id":"http://localhost/xapi/activity/41","objectType":"Activity"}],"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"Artefakt[,]quantifizierte[,]Metrik[,]Projekte[,]Prozess","score":{"min":0,"raw":1,"max":5,"scaled":0.2},"duration":"PT108.3S","completion":true}},{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41?subContentId=446cb9b3-f616-49ba-9f39-7470e88924b8","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5,"http://h5p.org/x-api/h5p-subContentId":"446cb9b3-f616-49ba-9f39-7470e88924b8"},"name":{"en-US":"Zuordnungsaufgabe"},"description":{"en-US":"Zuordnungsaufgabe"},"type":"http://adlnet.gov/expapi/activities/cmi.interaction","interactionType":"matching","source":[{"id":"0","description":{"en-US":"Feedback\n"}},{"id":"1","description":{"en-US":"Vergleich\n"}},{"id":"2","description":{"en-US":"Kontrolle\n"}},{"id":"3","description":{"en-US":"Beurteilung\n"}},{"id":"4","description":{"en-US":"Abschätzung\n"}},{"id":"5","description":{"en-US":"Transparenz\n"}},{"id":"6","description":{"en-US":"Beziehung\n"}},{"id":"7","description":{"en-US":"Gesund werden\n"}}],"correctResponsesPattern":["0[.]0[,]0[.]1[,]0[.]2[,]0[.]3[,]0[.]4[,]0[.]5[,]1[.]6[,]1[.]7"],"target":[{"id":"0","description":{"en-US":"Plus\n"}},{"id":"1","description":{"en-US":"minus\n"}}]}},"context":{"contextActivities":{"parent":[{"id":"http://localhost/xapi/activity/41","objectType":"Activity"}],"category":[{"id":"http://h5p.org/libraries/H5P.DragQuestion-1.13","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":8,"raw":6,"scaled":0.75},"completion":true,"success":false,"duration":"PT21.54S","response":"0[.]0[,]0[.]1[,]0[.]2[,]1[.]3[,]1[.]4[,]0[.]5[,]1[.]6[,]1[.]7"}}]',
                'expect_exception' => null,
                'contextid_to_instanceid_mapping' => [[41], [1]],
                'expected_result' => [1]
            ], [
                'xapi' => '{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5},"name":{"en-US":"Metriken Teil 1"},"interactionType":"compound","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":""}}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.InteractiveVideo-1.22","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":13,"raw":7,"scaled":0.5385},"completion":true,"success":false,"duration":"PT86.76S"}}',
                'expect_exception' => null,
                'contextid_to_instanceid_mapping' => [[41], [1]],
                'expected_result' => [1]
            ], [
                'xapi' => '["xapi",{"actor":{"name":"student","mbox":"stu@dent.de","objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/251","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":"df8tub15w"},"name":{"en-US":"Anfügen am Ende einer einfach verketteten Liste"}}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.10","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":6,"raw":0,"scaled":0},"completion":true,"duration":"PT0.97S","success":false}}]',
                'expect_exception' => invalid_parameter_exception::class,
                'contextid_to_instanceid_mapping' => [[251], [2]],
                'expected_result' => null
            ]
        ];
    }

    /**
     * @dataProvider provide_test_get_module_ids_from_xapi_data
     *
     *   # ANF-ID: [MVP9]
     */
    public function test_get_module_ids_from_xapi($xapi, $expect_exception, $contextid_to_instanceid_mapping, $expected_result) {
        // create mock
        $context_mock = Mockery::mock(context_module::class);
        for ($i = 0; $i < count($contextid_to_instanceid_mapping[0]); $i++) {
            $context_mock->shouldReceive('instance_by_id')->with($contextid_to_instanceid_mapping[0][$i])->andReturn((object)['instanceid' => $contextid_to_instanceid_mapping[1][$i]]);
        }

        // set context mock and make get_module_ids_from_xapi accessible
        $reflected_class = new ReflectionClass(score_h5p_learning_element::class);
        $property = $reflected_class->getProperty('context');
        $property->setAccessible(true);
        $property->setValue(null, $context_mock->mockery_getName());
        $method = $reflected_class->getMethod('get_module_ids_from_xapi');
        $method->setAccessible(true);

        // expect exception
        if ($expect_exception != null) {
            $this->expectException($expect_exception);
        }

        // call get_module_ids_from_xapi
        $res = $method->invokeArgs(null, [$xapi]);

        // validate result
        $this->assertEqualsCanonicalizing($expected_result, $res);
    }

    public static function provide_test_execute_data() {
        return [
            [
                'exception_get_adler_score_objects' => false,
                'core_xapi_statement_post_error' => null,
            ],
            [
                'exception_get_adler_score_objects' => false,
                'core_xapi_statement_post_error' => ['error' => true, 'exception' => (object)['message' => 'error']],
            ],
            [
                'exception_get_adler_score_objects' => false,
                'core_xapi_statement_post_error' => null,
            ],
            [
                'exception_get_adler_score_objects' => true,
                'core_xapi_statement_post_error' => null,
            ]
        ];
    }

    /**
     * @dataProvider provide_test_execute_data
     *
     * # ANF-ID: [MVP9]
     */
    public function test_execute($exception_get_adler_score_objects, $core_xapi_statement_post_error) {
        $xapi = "blub";

        $score_h5p_learning_element_mock = Mockery::mock(score_h5p_learning_element::class)->makePartial();
        $score_h5p_learning_element_mock->shouldAllowMockingProtectedMethods();
        $score_h5p_learning_element_mock->shouldReceive('get_module_ids_from_xapi')->andReturn([42]);


        $adler_score_helpers_mock = Mockery::mock(adler_score_helpers::class);
        if ($exception_get_adler_score_objects) {
            $this->expectException(moodle_exception::class);
            $adler_score_helpers_mock
                ->shouldReceive('get_adler_score_objects')
                ->andThrow(moodle_exception::class);
            // should not proceed to xapi event if get_adler_score_objects throws an exception
            $score_h5p_learning_element_mock->shouldNotReceive('call_external_function');
        } else {
            $adler_score_helpers_mock->shouldReceive('get_adler_score_objects')->andReturn([42]);

            if ($core_xapi_statement_post_error == null) {
                $score_h5p_learning_element_mock
                    ->shouldReceive('call_external_function')
                    ->with('core_xapi_statement_post', ['component' => 'mod_h5pactivity', 'requestjson' => $xapi], true)
                    ->andReturn(['error' => false]);
            } else {
                $score_h5p_learning_element_mock
                    ->shouldReceive('call_external_function')
                    ->with('core_xapi_statement_post', ['component' => 'mod_h5pactivity', 'requestjson' => $xapi], true)
                    ->andReturn($core_xapi_statement_post_error);
                $this->expectException(moodle_exception::class);
            }
        }
        di::set(adler_score_helpers::class, $adler_score_helpers_mock);

        $lib_mock = Mockery::mock(lib::class);
        $lib_mock
            ->shouldReceive('convert_adler_score_array_format_to_response_structure')
            ->with([42])
            ->andReturn([42]);
        di::set(lib::class, $lib_mock);


        $result = $score_h5p_learning_element_mock::execute($xapi);


        $this->assertEquals(['data' => [42]], $result);
    }

    /**
     * # ANF-ID: [MVP9]
     */
    public function test_execute_returns() {
        // this function just returns what get_adler_score_response_multiple_structure returns
        $lib_test = new lib_test();
        $lib_test->test_get_adler_score_response_multiple_structure(score_h5p_learning_element::class);
    }
}