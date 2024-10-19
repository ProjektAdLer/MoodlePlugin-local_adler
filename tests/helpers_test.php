<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler;


use local_adler\lib\adler_testcase;


class helpers_test extends adler_testcase {
    public function provide_test_course_is_adler_course_data(): array {
        return [
            'is adler course' => [['course_exist' => true, 'is_adler_course' => true, 'expected' => true]],
            'is not adler course' => [['course_exist' => true, 'is_adler_course' => false, 'expected' => false]],
            'does not exist' => [['course_exist' => false, 'is_adler_course' => false, 'expected' => false]]
        ];
    }

    /**
     * @dataProvider provide_test_course_is_adler_course_data
     *
     * # ANF-ID: [MVP12, MVP10, MVP9, MVP8, MVP7]
     */
    public function test_course_is_adler_course($data) {
        $course_id = 8001;
        if ($data['course_exist']) {
            $course_id = $this->getDataGenerator()->create_course()->id;
        }
        if ($data['is_adler_course']) {
            $this->getDataGenerator()->get_plugin_generator('local_adler')->create_adler_course_object($course_id);
        }

        $result = helpers::course_is_adler_course($course_id);

        $this->assertEquals($data['expected'], $result);
    }
}