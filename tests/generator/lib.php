<?php

global $CFG;
require_once($CFG->dirroot . '/lib/horde/framework/Horde/Support/Uuid.php');  # required on some installs (bitnami moodle on phils pc), unknown why

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
//    todo maybe rename
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
            $create_adler_score_item->id = $DB->insert_record('local_adler_course_modules', $create_adler_score_item);
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

    public function create_adler_section_object(int $section_id, array $params = array(), bool $insert = true) {
        global $DB;
        $default_params = [
            'section_id' => $section_id,
            'required_points_to_complete' => 100,
            'uuid' => (string) new Horde_support_Uuid,
            'timecreated' => 0,
            'timemodified' => 0
        ];
        $params = array_merge($default_params, $params);
        $create_adler_section_item = (object)$params;

        if ($insert) {
            $create_adler_section_item->id = $DB->insert_record('local_adler_sections', $create_adler_section_item);
        }
        return $create_adler_section_item;
    }

    /**
     * Create an adler condition
     *
     * @param int $section_id
     * @param array $depending_on_section_ids a condition is created which requires all sections in this array to be completed. "availability_condition" field in $params has higher priority
     * @param array $params
     * @param bool $insert If false: return the object without inserting it into the database
     * @return stdClass
     * @throws dml_exception
     */
    public function create_adler_condition(int $section_id, array $depending_on_section_ids, array $params = array(), bool $insert = true): stdClass {
        $condition = '';
        for ($i = 0; $i < count($depending_on_section_ids); $i++) {
            $condition .= '(' . $depending_on_section_ids[$i] . ')';
            if ($i < count($depending_on_section_ids) - 1) {
                $condition .= '^';
            }
        }

        global $DB;
        $default_params = [
            'availability_condition' => '{"op":"&","c":[{"type":"adler","condition":"' . $condition . '"}],"showc":[true]}',
        ];
        $params = array_merge($default_params, $params);

        $section = $DB->get_record('course_sections', ['id' => $section_id]);
        $section->availability = $params['availability_condition'];

        if ($insert) {
            $DB->update_record('course_sections', $section);
        }
        return $section;
    }
}
