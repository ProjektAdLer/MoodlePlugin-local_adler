<?php
class local_adler_generator extends component_generator_base {
    // https://github.com/call-learning/moodle-local_resourcelibrary/blob/master/tests/generator/lib.php

    /**
     * Create a course module
     *
     * @param int $module_id
     * @param array $params
     * @param bool $insert Set to false to return the object without inserting it into the database
     * @return stdClass
     * @throws dml_exception
     */
    public function create_adler_score_item(int $module_id, array $params = array(), bool $insert = true) {
        global $DB;
        $default_params = [
            'cmid' => $module_id,
            'score_max' => 100.0,
            'timecreated' => 0,
            'timemodified' => 0
        ];
        $params = array_merge($default_params, $params);
        $create_adler_score_item = (object)$params;

        if ($insert) {
            $create_adler_score_item->id = $DB->insert_record('local_adler_scores_items', $create_adler_score_item);
        }
        return $create_adler_score_item;
    }

    /**
     * Create an adler course object
     *
     * @param int $course_id
     * @param array $params
     * @param bool $insert Set to false to return the object without inserting it into the database
     * @return stdClass
     * @throws dml_exception
     */
    public function create_adler_course_object(int $course_id, array $params = array(), bool $insert = true) {
        global $DB;
        $default_params = [
            'course_id' => $course_id,
            'timecreated' => 0,
            'timemodified' => 0
        ];
        $params = array_merge($default_params, $params);
        $create_adler_course_item = (object)$params;

        if ($insert) {
            $create_adler_course_item->id = $DB->insert_record('local_adler_course', $create_adler_course_item);
        }
        return $create_adler_course_item;
    }
}
