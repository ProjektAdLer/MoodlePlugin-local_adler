<?php

namespace local_adler\external;

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;

class lib {
    public static function get_adler_score_response_multiple_structure(): external_function_parameters {
        return new external_function_parameters([
            'data' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'module_id' => new external_value(
                            PARAM_INT,
                            'moodle module id'),
                        'score' => new external_value(
                            PARAM_FLOAT,
                            'achieved (adler-file) score, if this field is missing completion api (or something simillar) is disabled for this element',
                            VALUE_OPTIONAL),
                    ),
                    'adler score for a module and the corresponding module id'
                ),
                'Moodle prefers having things of non-fixed size not on top level. Also this allows easier expansions like status codes. Contains a list of adler scores and their corresponding module ids',
            )
        ]);
    }

    /**
     * Convert an array of adler scores in format [<module_id>=><score>,..]
     * to the response structure ['module_id'=><module_id>, 'score'=><score>]
     *
     * @throws invalid_parameter_exception
     */
    public static function convert_adler_score_array_format_to_response_structure(array $scores): array {
        $result = array();
        foreach ($scores as $module_id => $score) {
            // check datatypes of $score
            if (!(is_float($score) || is_int($score) || is_bool($score))) {
                throw new invalid_parameter_exception('score must be an integer or bool');
            }

            if ($score !== false) {
                $result[] = array(
                    'module_id' => $module_id,
                    'score' => (float)$score
                );
            } else {
                $result[] = array(
                    'module_id' => $module_id
                );
            }
        }
        return $result;
    }
}


