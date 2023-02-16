<?php

namespace local_adler\external;


use context_module;
use local_adler\adler_score_helpers_mock;
use local_adler\lib\local_adler_externallib_testcase;
use local_adler\lib\static_mock_utilities_trait;
use moodle_exception;
use ReflectionClass;

defined('MOODLE_INTERNAL') || die();


global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');
require_once($CFG->dirroot . '/local/adler/tests/mocks.php');



class score_h5p_learning_element_mock extends score_h5p_learning_element {
    use static_mock_utilities_trait;

    public static function call_external_function($function, $args, $ajaxonly = false) {
        return static::mock_this_function(__FUNCTION__, func_get_args());
    }
}


class score_h5p_learning_element_test extends local_adler_externallib_testcase {
    public function test_execute() {
        // create user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // create course
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        // set as adler course
        $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object($course->id);

        // create course module
        $course_module = $this->getDataGenerator()->create_module('h5pactivity', array('course' => $course->id, 'completion' => 1));
        // set as adler course module
        $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_score_item($course_module->cmid);

        // enroll user
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // get contextid
        $context = context_module::instance($course_module->cmid);

        // setup adler_score_helpers_mock
        adler_score_helpers_mock::reset_data();
        adler_score_helpers_mock::set_enable_mock('get_achieved_scores');
        adler_score_helpers_mock::set_returns('get_achieved_scores', [[$course_module->cmid =>1]]);
        adler_score_helpers_mock::set_enable_mock('get_adler_score_objects');
        adler_score_helpers_mock::set_returns('get_adler_score_objects', [[1,2,3]]);
        // set $adler_score_helpers
        $reflected_class = new ReflectionClass(score_h5p_learning_element::class);
        $reflected_property = $reflected_class->getProperty('adler_score_helpers');
        $reflected_property->setAccessible(true);
        $reflected_property->setValue(score_h5p_learning_element::class, adler_score_helpers_mock::class);

        score_h5p_learning_element_mock::reset_data();
        score_h5p_learning_element_mock::set_returns('call_external_function', [["error"=>false], ["error"=>false], ["error"=>true, "exception"=>(object)['message'=>'test exception']]]);

        // generate test data
        $xapis = [
            '[{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5},"name":{"en-US":"Metriken Teil 1"},"interactionType":"compound","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":""}}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.InteractiveVideo-1.22","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":13,"raw":7,"scaled":0.5385},"completion":true,"success":false,"duration":"PT86.76S"}},{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41?subContentId=d72ecc5d-9f18-4017-aa4e-29f1932de84b","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5,"http://h5p.org/x-api/h5p-subContentId":"d72ecc5d-9f18-4017-aa4e-29f1932de84b"},"name":{"en-US":"Wörter einordnen"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p>Ziehe die Wörter in die richtigen Felder!</p>\n<br/>Eine __________ ist im Software Engineering eine __________ Aussage über ein __________, einen __________, oder __________."},"correctResponsesPattern":["Metrik[,]quantifizierte[,]Artefakt[,]Prozess[,]Projekte"]}},"context":{"contextActivities":{"parent":[{"id":"http://localhost/xapi/activity/41","objectType":"Activity"}],"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"Artefakt[,]quantifizierte[,]Metrik[,]Projekte[,]Prozess","score":{"min":0,"raw":1,"max":5,"scaled":0.2},"duration":"PT108.3S","completion":true}},{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41?subContentId=446cb9b3-f616-49ba-9f39-7470e88924b8","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5,"http://h5p.org/x-api/h5p-subContentId":"446cb9b3-f616-49ba-9f39-7470e88924b8"},"name":{"en-US":"Zuordnungsaufgabe"},"description":{"en-US":"Zuordnungsaufgabe"},"type":"http://adlnet.gov/expapi/activities/cmi.interaction","interactionType":"matching","source":[{"id":"0","description":{"en-US":"Feedback\n"}},{"id":"1","description":{"en-US":"Vergleich\n"}},{"id":"2","description":{"en-US":"Kontrolle\n"}},{"id":"3","description":{"en-US":"Beurteilung\n"}},{"id":"4","description":{"en-US":"Abschätzung\n"}},{"id":"5","description":{"en-US":"Transparenz\n"}},{"id":"6","description":{"en-US":"Beziehung\n"}},{"id":"7","description":{"en-US":"Gesund werden\n"}}],"correctResponsesPattern":["0[.]0[,]0[.]1[,]0[.]2[,]0[.]3[,]0[.]4[,]0[.]5[,]1[.]6[,]1[.]7"],"target":[{"id":"0","description":{"en-US":"Plus\n"}},{"id":"1","description":{"en-US":"minus\n"}}]}},"context":{"contextActivities":{"parent":[{"id":"http://localhost/xapi/activity/41","objectType":"Activity"}],"category":[{"id":"http://h5p.org/libraries/H5P.DragQuestion-1.13","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":8,"raw":6,"scaled":0.75},"completion":true,"success":false,"duration":"PT21.54S","response":"0[.]0[,]0[.]1[,]0[.]2[,]1[.]3[,]1[.]4[,]0[.]5[,]1[.]6[,]1[.]7"}}]',
        ];
        for ($i = 0; $i < count($xapis); $i++) {
            $xapi = $xapis[$i];

            // replace xapi/activity/41 with xapi/activity/$context->id
            $xapi = str_replace('xapi/activity/41', 'xapi/activity/' . $context->id, $xapi);
            // replace "account":{"name":"3" with "account":{"name":"$user->id"
            $xapi = str_replace('"account":{"name":"3"', '"account":{"name":"' . $user->id . '"', $xapi);


            // call CUD
            $result = score_h5p_learning_element_mock::execute($xapi);


            // validate static function calls
            $this->assertEquals($xapi, score_h5p_learning_element_mock::get_calls('call_external_function')[$i][1]['requestjson']);
            $this->assertEquals('mod_h5pactivity', score_h5p_learning_element_mock::get_calls('call_external_function')[$i][1]['component']);
            $this->assertEquals([$course_module->cmid], adler_score_helpers_mock::get_calls('get_adler_score_objects')[$i][0]);

            // validate result
            $this->assertCount(1, $result);
            $this->assertEquals(['module_id' => $course_module->cmid, 'score' => 1], $result['data'][0]);
        }

        // test failed_to_get_adler_score
        $xapi = $xapis[0];
        $xapi = str_replace('xapi/activity/41', 'xapi/activity/' . $context->id, $xapi);

        // enable fail for get_achieved_scores
        // setup adler_score_helpers_mock
        adler_score_helpers_mock::reset_data();
        adler_score_helpers_mock::set_enable_mock('get_achieved_scores');
        adler_score_helpers_mock::set_exceptions('get_achieved_scores', [new moodle_exception('blub')]);
        adler_score_helpers_mock::set_enable_mock('get_adler_score_objects');
        adler_score_helpers_mock::set_returns('get_adler_score_objects', [[1,2,3]]);

        // call CUD
        $expected_exception_thrown = false;
        try {
            score_h5p_learning_element_mock::execute($xapi);
        } catch (moodle_exception $e) {
            $expected_exception_thrown = true;

            $this->assertEquals('failed_to_get_adler_score', $e->errorcode);
        }
        $this->assertTrue($expected_exception_thrown, 'expected exception not thrown');

        // test xapi process failed
        // enable fail for get_achieved_scores
        // setup adler_score_helpers_mock
        adler_score_helpers_mock::reset_data();
        adler_score_helpers_mock::set_enable_mock('get_achieved_scores');
        adler_score_helpers_mock::set_exceptions('get_achieved_scores', [new moodle_exception('blub')]);
        adler_score_helpers_mock::set_enable_mock('get_adler_score_objects');
        adler_score_helpers_mock::set_returns('get_adler_score_objects', [[1,2,3]]);

        $expected_exception_thrown = false;
        try {
            score_h5p_learning_element_mock::execute($xapi);
        } catch (moodle_exception $e) {
            $this->assertEquals('failed_to_process_xapi', $e->errorcode);
            $expected_exception_thrown = true;
        }
        $this->assertTrue($expected_exception_thrown, 'expected exception not thrown');
    }

    public function test_execute_returns() {
        // this function just returns what get_adler_score_response_multiple_structure returns
        require_once(__DIR__ . '/lib_test.php');
        (new lib_test())->test_get_adler_score_response_multiple_structure(score_h5p_learning_element::class);
    }
}