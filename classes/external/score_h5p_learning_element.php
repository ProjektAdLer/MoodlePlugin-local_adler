<?php

namespace local_adler\external;

use coding_exception;
use context;
use external_api;
use external_function_parameters;
use external_value;
use local_adler\adler_score;
use local_adler\adler_score_helpers;
use moodle_exception;


class score_h5p_learning_element extends external_api {
    protected static string $adler_score = adler_score::class;
    protected static string $adler_score_helpers = adler_score_helpers::class;


    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'xapi' => new external_value(PARAM_RAW, 'xapi json payload for h5p module', VALUE_REQUIRED),
            )
        );
    }


    public static function execute_returns(): external_function_parameters {
        return lib::get_adler_score_response_multiple_structure();
    }


    /** Get array of all course_module ids of the given xapi event
     * @param $xapi string xapi json payload
     * @return array of course_module ids
     * @throws coding_exception
     */
    private static function get_module_ids_from_xapi(string $xapi): array {
        $xapi = json_decode($xapi);
        $module_ids = array();
        foreach ($xapi as $statement) {
            $url = explode('/', $statement->object->id);
            $url = explode('?', end($url));  // some object->id's have a query string
            $context_id = $url[0];
            $module_id = context::instance_by_id($context_id)->instanceid;
            // add module id to array if not already in it
            if (!in_array($module_id, $module_ids)) {
                $module_ids[] = $module_id;
            }
        }
        return $module_ids;
    }


    /** process xapi payload and return array of adler_score objects
     * xapi payload is proxied to core xapi library
     * @param $xapi string xapi json payload
     * @return array of adler_score objects
     * @throws moodle_exception
     */
    public static function execute($xapi): array {
        $params = self::validate_parameters(self::execute_parameters(), array(
            'xapi' => $xapi,
        ));
        $xapi = $params['xapi'];

        // first check if the modules support adler scoring
        // if one cm is not part of an adler course or is not an adler cm an exception is thrown
        $module_ids = static::get_module_ids_from_xapi($xapi);
        $adler_scores = static::$adler_score_helpers::get_adler_score_objects($module_ids);

        // proxy xapi payload to core xapi library
        $result = static::call_external_function('core_xapi_statement_post', array(
            'component' => 'mod_h5pactivity',
            'requestjson' => $xapi
        ), true);

        if ($result['error']) {
            throw new moodle_exception('failed_to_process_xapi', 'local_adler', null, null, $result['exception']->message);
        }

        // get adler score
        try {
            $scores = static::$adler_score_helpers::get_achieved_scores(null, null, $adler_scores);
        } catch (moodle_exception $e) {
            debugging('Failed to get adler scores, but xapi statements are already processed', E_ERROR);
            throw new moodle_exception('failed_to_get_adler_score', 'local_adler', '', $e->getMessage());
        }

        // convert $scores to return format
        return ['data' => lib::convert_adler_score_array_format_to_response_structure($scores)];
    }
}
