<?php

namespace local_adler\external;

use coding_exception;
use context;
use core\di;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use invalid_parameter_exception;
use local_adler\adler_score_helpers;
use local_logging\logger;
use moodle_exception;


class score_h5p_learning_element extends external_api {
    private static string $context = context::class;


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
     * @throws coding_exception|invalid_parameter_exception
     */
    protected static function get_module_ids_from_xapi(string $xapi): array {
        $xapi = json_decode($xapi);

        if (is_object($xapi)) {
            $xapi = array($xapi);
        }

        $module_ids = array();
        foreach ($xapi as $statement) {
            if (!is_object($statement)) {
                throw new invalid_parameter_exception("xapi statement is not an object");
            }

            $url = explode('/', $statement->object->id);
            $url = explode('?', end($url));  // some object->id's have a query string
            $context_id = $url[0];
            $module_id = self::$context::instance_by_id($context_id)->instanceid;
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
        $logger = new logger('local_adler', 'score_h5p_learning_element');

        $params = self::validate_parameters(self::execute_parameters(), array(
            'xapi' => $xapi,
        ));
        $xapi = $params['xapi'];

        // first check if the modules support adler scoring
        // if one cm is not part of an adler course or is not an adler cm an exception is thrown
        $module_ids = static::get_module_ids_from_xapi($xapi);
        $adler_scores = di::get(adler_score_helpers::class)::get_adler_score_objects($module_ids);

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
            $results = di::get(adler_score_helpers::class)::get_completion_state_and_achieved_scores(null, null, $adler_scores);
        } catch (moodle_exception $e) {
            $logger->error('Failed to get adler scores, but xapi statements are already processed');
            throw new moodle_exception('failed_to_get_adler_score', 'local_adler', '', $e->getMessage());
        }

        // convert $scores to return format
        return ['data' => di::get(lib::class)::convert_adler_score_array_format_to_response_structure($results)];
    }
}
