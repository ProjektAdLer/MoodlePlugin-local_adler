<?php

namespace local_adler\external;

use core\di;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_value;
use dml_missing_record_exception;
use invalid_parameter_exception;
use local_adler\adler_score_helpers;
use local_adler\moodle_core;
use moodle_exception;
use require_login_exception;
use restricted_context_exception;

class score_get_element_scores extends external_api {

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
                $context = di::get(moodle_core::class)->context_module_instance($module_id);
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
        $results = di::get(adler_score_helpers::class)::get_completion_state_and_achieved_scores($module_ids);

        // convert format return
        return ['data' => lib::convert_adler_score_array_format_to_response_structure($results)];
    }
}
