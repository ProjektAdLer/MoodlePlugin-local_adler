<?php


use local_adler\lib\adler_testcase;


global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');
require_once($CFG->dirroot . '/local/adler/db/uninstall.php');

class uninstall_test extends adler_testcase {
    public function test_xmldb_local_adler_uninstall() {
        global $DB;

        $adler_generator = $this->getDataGenerator()->get_plugin_generator('local_adler');

        // create course
        $course = $this->getDataGenerator()->create_course();
        // make courses adler courses
        $adler_generator->create_adler_course_object($course->id);

        // create adler sections
        $sections = array_values($DB->get_records('course_sections', ['course' => $course->id]));
        for ($i = 0; $i < count($sections); $i++) {
            $adler_generator->create_adler_section($sections[$i]->id);
            if ($i > 0) {
                $adler_generator->create_adler_condition($sections[$i]->id, [(int)$sections[$i - 1]->id]);
            }
        }

        // call function
        xmldb_local_adler_uninstall();

        // check if all adler conditions were deleted
        foreach ($sections as $section) {
            $this->assertEquals(null, $DB->get_record('course_sections', ['id' => $section->id])->availability);
        }
    }

    public static function provide_test_remove_adler_objects_data() {
        return [
            'simple 1' => [
                'condition' => '{"op":"&","c":[{"type":"adler","condition":"(73)^(75)"}],"showc":[true]}',
                'expected' => null
            ],
            'simple 2' => [
                'condition' => '{"op":"&","c":[{"type":"adler","condition":"(73)^(75)"},{"type":"adler","condition":"(73)^(75)"}],"showc":[true,true]}',
                'expected' => null
            ],
            'simple 3' => [
                'condition' => '{"op":"|","c":[{"type":"adler","condition":"(73)^(75)"}],"show":true}',
                'expected' => null
            ],
            'deep 1' => [
                'condition' => '{"op":"&","c":[{"op":"&","c":[{"type":"date","d":">=","t":1682250400}]},{"op":"&","c":[{"op":"&","c":[{"type":"date","d":">=","t":1681550400}]},{"type":"adler","condition":"155^154"}]},{"type":"date","d":">=","t":1681290400}, {"type":"adler","condition":"155^154v53"}],"showc":[true,false,true,true]}',
                'expected' => '{"op":"&","c":[{"op":"&","c":[{"type":"date","d":">=","t":1682250400}]},{"op":"&","c":[{"op":"&","c":[{"type":"date","d":">=","t":1681550400}]}]},{"type":"date","d":">=","t":1681290400}],"showc":[true,false,true]}'
            ],
            'deep 2' => [
                'condition' => '{"op":"|","c":[{"op":"&","c":[{"type":"date","d":">=","t":1682250400}]},{"op":"&","c":[{"op":"&","c":[{"type":"date","d":">=","t":1681550400}]},{"type":"adler","condition":"155^154"}]},{"type":"date","d":">=","t":1681290400}, {"type":"adler","condition":"155^154v53"}],"show":true}',
                'expected' => '{"op":"|","c":[{"op":"&","c":[{"type":"date","d":">=","t":1682250400}]},{"op":"&","c":[{"op":"&","c":[{"type":"date","d":">=","t":1681550400}]}]},{"type":"date","d":">=","t":1681290400}],"show":true}'
            ],
            'deep 3' => [
                'condition' => '{"op":"&","c":[{"op":"&","c":[{"type":"adler","condition":"155^154"}]},{"type":"date","d":">=","t":1681290400},{"type":"adler","condition":"155^154v53"}],"showc":[true,false,true]}',
                'expected' => '{"op":"&","c":[{"type":"date","d":">=","t":1681290400}],"showc":[false]}'
            ],
            'deep 4' => [
                'condition' => '{"op":"|","c":[{"op":"&","c":[{"type":"adler","condition":"155^154"}]},{"type":"date","d":">=","t":1681290400},{"type":"adler","condition":"155^154v53"}],"show":true}',
                'expected' => '{"op":"|","c":[{"type":"date","d":">=","t":1681290400}],"show":true}'
            ],
        ];
    }

    /**
     * @dataProvider provide_test_remove_adler_objects_data
     */
    public function test_remove_adler_objects(string $condition, ?string $expected) {
        $res = remove_adler_objects($condition);
        $this->assertEquals($expected, $res);
    }
}