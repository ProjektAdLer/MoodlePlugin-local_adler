<?php

namespace local_adler\external;


use context_module;
use externallib_advanced_testcase;
use local_adler\dsl_score;

defined('MOODLE_INTERNAL') || die();


global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/webservice/tests/helpers.php');


class dsl_score_mock {
    public static array $calls = array();

    public static function get_achieved_scores($module_ids) {
        self::$calls[] = $module_ids;

        $result = array();
        foreach ($module_ids as $module_id) {
            $result[$module_id] = 42.0;
        }
        return $result;
    }
}

class score_h5p_learning_element_mock extends score_h5p_learning_element {
    public static array $calls = array();

    public static function call_external_function($functionname, $params, $ajaxonly=false) {
        static::$dsl_score = dsl_score_mock::class;

        self::$calls[] = array(
            'functionname' => $functionname,
            'params' => $params,
            'ajaxonly' => $ajaxonly,
        );
    }
}


class score_h5p_learning_element_test extends externallib_advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest(true);
    }

    public function test_execute() {
        // create user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // create course
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // create course module
        $course_module = $this->getDataGenerator()->create_module('h5pactivity', array('course' => $course->id, 'completion' => 1));

        // get contextid
        $context = context_module::instance($course_module->cmid);

        // TODO add further xapi examples
        // generate test data
        $xapi = '[{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5},"name":{"en-US":"Metriken Teil 1"},"interactionType":"compound","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":""}}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.InteractiveVideo-1.22","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":13,"raw":7,"scaled":0.5385},"completion":true,"success":false,"duration":"PT86.76S"}},{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41?subContentId=d72ecc5d-9f18-4017-aa4e-29f1932de84b","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5,"http://h5p.org/x-api/h5p-subContentId":"d72ecc5d-9f18-4017-aa4e-29f1932de84b"},"name":{"en-US":"Wörter einordnen"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p>Ziehe die Wörter in die richtigen Felder!</p>\n<br/>Eine __________ ist im Software Engineering eine __________ Aussage über ein __________, einen __________, oder __________."},"correctResponsesPattern":["Metrik[,]quantifizierte[,]Artefakt[,]Prozess[,]Projekte"]}},"context":{"contextActivities":{"parent":[{"id":"http://localhost/xapi/activity/41","objectType":"Activity"}],"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"Artefakt[,]quantifizierte[,]Metrik[,]Projekte[,]Prozess","score":{"min":0,"raw":1,"max":5,"scaled":0.2},"duration":"PT108.3S","completion":true}},{"actor":{"name":"user","objectType":"Agent","account":{"name":"3","homePage":"http://localhost"}},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"http://localhost/xapi/activity/41?subContentId=446cb9b3-f616-49ba-9f39-7470e88924b8","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":5,"http://h5p.org/x-api/h5p-subContentId":"446cb9b3-f616-49ba-9f39-7470e88924b8"},"name":{"en-US":"Zuordnungsaufgabe"},"description":{"en-US":"Zuordnungsaufgabe"},"type":"http://adlnet.gov/expapi/activities/cmi.interaction","interactionType":"matching","source":[{"id":"0","description":{"en-US":"Feedback\n"}},{"id":"1","description":{"en-US":"Vergleich\n"}},{"id":"2","description":{"en-US":"Kontrolle\n"}},{"id":"3","description":{"en-US":"Beurteilung\n"}},{"id":"4","description":{"en-US":"Abschätzung\n"}},{"id":"5","description":{"en-US":"Transparenz\n"}},{"id":"6","description":{"en-US":"Beziehung\n"}},{"id":"7","description":{"en-US":"Gesund werden\n"}}],"correctResponsesPattern":["0[.]0[,]0[.]1[,]0[.]2[,]0[.]3[,]0[.]4[,]0[.]5[,]1[.]6[,]1[.]7"],"target":[{"id":"0","description":{"en-US":"Plus\n"}},{"id":"1","description":{"en-US":"minus\n"}}]}},"context":{"contextActivities":{"parent":[{"id":"http://localhost/xapi/activity/41","objectType":"Activity"}],"category":[{"id":"http://h5p.org/libraries/H5P.DragQuestion-1.13","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":8,"raw":6,"scaled":0.75},"completion":true,"success":false,"duration":"PT21.54S","response":"0[.]0[,]0[.]1[,]0[.]2[,]1[.]3[,]1[.]4[,]0[.]5[,]1[.]6[,]1[.]7"}}]';
        // replace xapi/activity/41 with xapi/activity/$context->id
        $xapi = str_replace('xapi/activity/41', 'xapi/activity/' . $context->id, $xapi);



        // call CUD
        $result = score_h5p_learning_element_mock::execute($xapi);

        // validate static function calls
        $this->assertEquals($xapi, score_h5p_learning_element_mock::$calls[0]['params']['requestjson']);
        $this->assertEquals([$course_module->cmid], dsl_score_mock::$calls[0]);

        // validate result
        $this->assertCount(1, $result);
        $this->assertEquals(['module_id'=>$course_module->cmid, 'score'=>42], $result[0]);
    }
}