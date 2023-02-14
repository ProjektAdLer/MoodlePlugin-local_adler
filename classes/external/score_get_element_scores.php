<?php

namespace local_adler\external;

use context_module;
use dml_missing_record_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use invalid_parameter_exception;
use local_adler\dsl_score;
use local_adler\dsl_score_helpers;
use moodle_exception;
use require_login_exception;
use restricted_context_exception;

class score_get_element_scores extends external_api {
    protected static string $dsl_score = dsl_score::class;
    protected static string $dsl_score_helpers = dsl_score_helpers::class;
    protected static string $context_module = context_module::class;

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'module_ids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
                )
            )
        );
    }

    public static function execute_returns(): external_function_parameters {
        return lib::get_adler_score_response_multiple_structure();
    }

    /**
     * @throws restricted_context_exception
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function execute(array $module_ids): array {
        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), array('module_ids' => $module_ids));
        $module_ids = $params['module_ids'];

        // Permission check
        $failed_items = array();
        foreach ($module_ids as $module_id) {
            try {
                $context = static::$context_module::instance($module_id);
            } catch (dml_missing_record_exception $e) {
                // module does not exist
                $failed_items[] = $module_id;
                continue;
            }
            try {
                static::validate_context($context);
            } catch (require_login_exception $e) {
                // user not enrolled
                $failed_items[] = $module_id;
            }
        }
        if (count($failed_items) > 0) {
            // Don't give a malicious user more information than necessary
            throw new moodle_exception(
                'invalidmoduleids',
                'local_adler',
                '',
                NULL,
                'User not enrolled or modules do next exist for ids: ' . implode(', ', $failed_items));
        }

        // get scores
        $scores = static::$dsl_score_helpers::get_achieved_scores($module_ids);

        // convert format return
        return ['data' => lib::convert_adler_score_array_format_to_response_structure($scores)];
    }
}
