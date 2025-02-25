<?php

namespace local_adler\external;

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use local_adler\adler_score;
use moodle_exception;

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
     * @param adler_score[] $results [cmid => adler_score|false]
     * @return array ['module_id'=><module_id>, 'score'=><score>, 'completed'=><completion_state>]
     * @throws moodle_exception
     */
    public static function convert_adler_score_array_format_to_response_structure(array $results): array {
        $response_data = array();
        /** @var int $cmid */
        /** @var adler_score|false $adler_score */
        foreach ($results as $cmid => $adler_score) {
            if ($adler_score === false) {
                $response_data[] = array(
                    'module_id' => $cmid
                );
            } else {
                $response_data[] = array(
                    'module_id' => $adler_score->get_cmid(),
                    'score' => (float)$adler_score->get_score_by_completion_state(),
                    'completed' => $adler_score->get_completion_state()
                );
            }
        }
        return $response_data;
    }
}


