<?php

defined('MOODLE_INTERNAL') || die();

class local_adler_generator extends component_generator_base {
    // TODO: Implement this class.
    # https://github.com/call-learning/moodle-local_resourcelibrary/blob/master/tests/generator/lib.php

    /**
     * Create a course module
     *
     * @param int $module_id
     * @param array $params
     * @param bool $insert Set to false to return the object without inserting it into the database
     * @return stdClass
     */
    public function create_dsl_score_item(int $module_id, $params = array(), bool $insert = true) {
        global $DB;
        $default_params = [
            'course_modules_id' => $module_id,
            'type' => 'score',
            'score_min' => 0.0,
            'score_max' => 100.0,
            'timecreated' => 0,
            'timemodified' => 0
        ];
        $params = array_merge($default_params, $params);
        $create_dsl_score_item = (object)$params;

        if ($insert) {
            $create_dsl_score_item->id = $DB->insert_record('local_adler_score_items', $create_dsl_score_item);
        }
        return $create_dsl_score_item;
    }
}