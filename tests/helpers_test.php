<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler;


use local_adler\lib\adler_testcase;
use moodle_exception;
use Throwable;


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

    public function provide_test_is_primitive_learning_element_data() {
        return [
            'is primitive' =>[['type'=>'primitive']],
            'is not primitive' =>[['type'=>'h5pactivity']],
            'wrong format' =>[['type'=>'wrong_format']]
        ];
    }

    /**
     * @dataProvider provide_test_is_primitive_learning_element_data
     *
     * # ANF-ID: [MVP10]
     */
    public function test_is_primitive_learning_element($data) {
        // create course
        $course = $this->getDataGenerator()->create_course();
        // create user
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user);
        // create course module
        if ($data['type'] == 'primitive' || $data['type'] == 'wrong_format') {
            $course_module = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);
        } else {
            $course_module = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course->id]);
        }
        if ($data['type'] != 'wrong_format') {
            $course_module = get_fast_modinfo($course->id)->get_cm($course_module->cmid);
        }

        // call function
        $exception = null;
        $result = null;
        try {
            $result = helpers::is_primitive_learning_element($course_module);
        } catch (Throwable $e) {
            $exception = $e;
        }

        // check result
        if ($data['type'] == 'wrong_format') {
            $this->assertStringContainsString(moodle_exception::class, get_class($exception));
        } else if ($exception != null) {
            $this->fail('Unexpected exception: ' . $exception->getMessage());
        }

        $this->assertEquals($data['type'] == 'primitive', $result);
    }
}