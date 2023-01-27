<?php

namespace local_adler\external;

use external_single_structure;
use external_value;

class lib {
    public static function get_adler_score_response_single_structure() {
        return new external_single_structure(
            array(
                'module_id' => new external_value(
                    PARAM_INT,
                    'moodle module id'),
                'score' => new external_value(
                    PARAM_FLOAT,
                    'achieved (dsl-file) score,if this field is missing it was not possible to get the adler score for this module',
                    VALUE_OPTIONAL),
            )
        );
    }

    // TODO: use this function everywhere and adjust tests
    public static function convert_adler_score_array_format_to_response_structure(array $scores) {
        $result = array();
        foreach ($scores as $module_id => $score) {
            if ($score !== false) {
                $result[] = array(
                    'module_id' => $module_id,
                    'score' => $score
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


