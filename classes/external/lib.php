<?php

namespace local_adler\external;

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
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
                            'achieved (adler-file) score, if this field is missing completion api (or something similar) is disabled for this element',
                            VALUE_OPTIONAL),
                        'completed' => new external_value(
                            PARAM_BOOL,
                            'true if the element is completed, false otherwise. If this field is missing completion api (or something similar) is disabled for this element',
                            VALUE_OPTIONAL),
                    ),
                    'adler score for a module and the corresponding module id'
                ),
                'Moodle prefers having things of non-fixed size not on top level. Also this allows easier expansions like status codes. Contains a list of adler scores and their corresponding module ids',
            )
        ]);
    }

    /**
     * Convert an array of adler scores in format [<module_id>=>['score'=>1, 'completion_state'=>true],..]
     * to the response structure ['module_id'=><module_id>, 'score'=><score>, 'completed'=><completion_state>]
     *
     * @param array[] $results
     * @throws invalid_parameter_exception
     */
    public static function convert_adler_score_array_format_to_response_structure(array $results): array {
        $response_data = array();
        foreach ($results as $module_id => $result) {
            if (!(is_array($result) || is_bool($result))) {
                throw new invalid_parameter_exception('score must be an array or a boolean');
            }

            if ($result !== false) {
                $response_data[] = array(
                    'module_id' => $module_id,
                    'score' => (float)$result['score'],
                    'completed' => $result['completion_state']
                );
            } else {
                $response_data[] = array(
                    'module_id' => $module_id
                );
            }
        }
        return $response_data;
    }
}


