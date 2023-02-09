<?php

namespace local_adler\external;

use coding_exception;
use context;
use external_api;
use external_function_parameters;
use external_value;
use local_adler\dsl_score;
use local_adler\dsl_score_helpers;
use moodle_exception;


class score_h5p_learning_element extends external_api {
    protected static $dsl_score = dsl_score::class;
    protected static $dsl_score_helpers = dsl_score_helpers::class;


    public static function execute_parameters() {
        return new external_function_parameters(
            array(
                'xapi' => new external_value(PARAM_RAW, 'xapi json payload for h5p module', VALUE_REQUIRED),
            )
        );
    }


    public static function execute_returns() {
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


    /** process xapi payload and return array of dsl_score objects
     * xapi payload is proxied to core xapi library
     * @param $xapi string xapi json payload
     * @return array of dsl_score objects
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
        $dsl_scores = static::$dsl_score_helpers::get_dsl_score_objects($module_ids);

        // proxy xapi payload to core xapi library
        $result = static::call_external_function('core_xapi_statement_post', array(
            'component' => 'h5pactivity',
            'requestjson' => $xapi
        ), true);
        // TODO: check response

        // get dsl score
        try {
            $scores = static::$dsl_score_helpers::get_achieved_scores(null, null, $dsl_scores);
        } catch (moodle_exception $e) {
            debugging('Failed to get DSL scores, but xapi statements are already processed', E_ERROR);
            throw new moodle_exception('failed_to_get_dsl_score', 'local_adler', '', $e->getMessage());
        }

        // convert $scores to return format
        return ['data' => lib::convert_adler_score_array_format_to_response_structure($scores)];
    }
}
