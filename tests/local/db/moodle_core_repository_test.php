<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler;

global $CFG;

use context_coursecat;
use core\di;
use dml_exception;
use local_adler\lib\adler_testcase;
use local_adler\local\db\moodle_core_repository;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class moodle_core_repository_test extends adler_testcase {
    /**
     * Data provider for category role assignment tests
     */
    public function provide_category_role_test_data() {
        return [
            'user_has_role' => [true, false, 1],
            'selective_assignment' => [true, true, 1],
            'no_roles' => [false, false, 0]
        ];
    }

    /**
     * @dataProvider provide_category_role_test_data
     */
    public function test_get_category_ids_where_user_has_role($assign_role, $create_second_category, $expected_count) {
        $moodle_core_repository = di::get(moodle_core_repository::class);
        $context_level = CONTEXT_COURSECAT;

        $user = $this->getDataGenerator()->create_user();
        $category = $this->getDataGenerator()->create_category();
        $role_id = $moodle_core_repository->get_role_id_by_shortname('teacher');

        // Conditionally assign the role to the user in the category
        if ($assign_role) {
            $context = context_coursecat::instance($category->id);
            role_assign($role_id, $user->id, $context->id);
        }

        // Conditionally create a second category
        if ($create_second_category) {
            $category2 = $this->getDataGenerator()->create_category();
        }

        // Call the function
        $result = $moodle_core_repository->get_category_ids_where_user_has_role(
            $user->id,
            $role_id,
            $context_level
        );

        // Check the result
        $this->assertCount($expected_count, $result);

        if ($expected_count > 0) {
            $this->assertEquals($category->id, reset($result));
        }
    }

    public function test_get_role_id_by_shortname() {
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // call function
        $result = $moodle_core_repository->get_role_id_by_shortname('student');

        // check result
        $this->assertEquals(5, $result);

        // error case
        // call function
        $result = $moodle_core_repository->get_role_id_by_shortname('nonexistentrole');

        $this->assertEquals(false, $result);
    }

    public function test_get_user_id_by_username() {
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // call function
        $result = $moodle_core_repository->get_user_id_by_username('admin');

        // check result
        $this->assertEquals(2, $result);

        // error case
        // call function
        $result = $moodle_core_repository->get_user_id_by_username('nonexistentuser');

        $this->assertEquals(false, $result);
    }

    public static function provide_true_false_data() {
        return [
            'true' => [true],
            'false' => [false]
        ];
    }

    /**
     * @dataProvider provide_true_false_data
     */
    public function test_get_grade_item($exists) {
        $moodle_core_repository = di::get(moodle_core_repository::class);

        if ($exists) {
            // create grade_item
            $course = $this->getDataGenerator()->create_course();
            $grade_item = $this->getDataGenerator()->create_grade_item(['courseid' => $course->id, 'itemmodule' => 'url', 'iteminstance' => 1]);

            // call function
            $result = $moodle_core_repository->get_grade_item($grade_item->itemmodule, $grade_item->iteminstance);

            // check result
            $this->assertEquals($grade_item->id, $result->id);
        } else {
            // error case
            $this->expectException(dml_exception::class);

            // call function
            $moodle_core_repository->get_grade_item('url', 1);
        }
    }

    public function test_update_grade_item_record() {
        global $DB;
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // create grade_item
        $course = $this->getDataGenerator()->create_course();
        $grade_item = $this->getDataGenerator()->create_grade_item(['courseid' => $course->id, 'itemmodule' => 'url', 'iteminstance' => 1]);

        // call function
        $moodle_core_repository->update_grade_item_record($grade_item->id, ['gradepass' => 100]);

        // check result
        $result = $DB->get_record('grade_items', ['id' => $grade_item->id]);
        $this->assertEquals(100, $result->gradepass);
    }

    public function test_update_course_module_record() {
        global $DB;
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // create course_module
        $course = $this->getDataGenerator()->create_course();
        $course_module = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // call function
        $moodle_core_repository->update_course_module_record($course_module->cmid, ['completion' => 2]);

        // check result
        $result = $DB->get_record('course_modules', ['id' => $course_module->cmid]);
        $this->assertEquals(2, $result->completion);
    }

    public function test_get_cms_with_module_name_by_course_id() {
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Create a course module
        $course_module = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);

        // Call the function
        $result = $moodle_core_repository->get_cms_with_module_name_by_course_id($course->id);

        // Check the result
        $this->assertCount(1, $result);
        $this->assertEquals($course_module->id, reset($result)->instance);
        $this->assertEquals('url', reset($result)->modname);
    }

    public function test_get_course_from_course_id() {
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Call the method
        $result = $moodle_core_repository->get_course_from_course_id($course->id);

        // Check the result
        $this->assertEquals($course->id, $result->id);
        $this->assertEquals($course->fullname, $result->fullname);
        $this->assertEquals($course->shortname, $result->shortname);
    }

    /**
     * # ANF-ID: [MVP12]
     */
    public function test_get_course_modules_by_section_id() {
        // create course
        $course = $this->getDataGenerator()->create_course();

        // create course module
        $course_module1 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $course_module2 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        // get section id
        $section_id = get_fast_modinfo($course->id)->get_cm($course_module2->cmid)->section;

        // create some other course with module (should not be included in result)
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('assign', ['course' => $course2->id]);

        // call function
        $db_course_modules = di::get(moodle_core_repository::class)->get_course_modules_by_section_id($section_id);

        // check result
        $this->assertEqualsCanonicalizing([$course_module1->cmid, $course_module2->cmid], array_keys($db_course_modules));
    }

    public function test_get_all_moodle_course_modules() {
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Create multiple course modules
        $course_module1 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $course_module2 = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        // Call the method
        $result = $moodle_core_repository->get_all_moodle_course_modules();

        // Check the result
        $this->assertArrayHasKey($course_module1->cmid, $result);
        $this->assertArrayHasKey($course_module2->cmid, $result);
        $this->assertEquals($course_module1->cmid, $result[$course_module1->cmid]->id);
        $this->assertEquals($course_module2->cmid, $result[$course_module2->cmid]->id);
    }

    public function test_get_moodle_section() {
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Create a section
        $section = $this->getDataGenerator()->create_course_section(['course' => $course->id, 'section' => 1]);

        // Call the method
        $result = $moodle_core_repository->get_moodle_section($section->id);

        // Check the result
        $this->assertEquals($section->id, $result->id);
        $this->assertEquals($section->course, $result->course);
    }

    public function test_get_all_moodle_sections() {
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Create multiple sections
        $section1 = $this->getDataGenerator()->create_course_section(['course' => $course->id, 'section' => 1]);
        $section2 = $this->getDataGenerator()->create_course_section(['course' => $course->id, 'section' => 2]);

        // Call the method
        $result = $moodle_core_repository->get_all_moodle_sections();

        // Check the result
        $this->assertArrayHasKey($section1->id, $result);
        $this->assertArrayHasKey($section2->id, $result);
        $this->assertEquals($section1->id, $result[$section1->id]->id);
        $this->assertEquals($section2->id, $result[$section2->id]->id);
    }

    public function test_update_moodle_section() {
        global $DB;
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Create a section
        $section = $this->getDataGenerator()->create_course_section(['course' => $course->id, 'section' => 1]);
        $section = $DB->get_record('course_sections', ['id' => $section->id]);

        // Update the section
        $section->name = 'Updated Section Name';
        $moodle_core_repository->update_moodle_section($section);

        // Retrieve the updated section
        $updated_section = $DB->get_record('course_sections', ['id' => $section->id]);

        // Check the result
        $this->assertEquals('Updated Section Name', $updated_section->name);
    }

    public function test_get_admin_services_site_admin_token() {
        global $DB;
        $moodle_core_repository = di::get(moodle_core_repository::class);

        // Create a user
        $user = $this->getDataGenerator()->create_user();

        // Create the external service if it doesn't exist
        $service = $DB->get_record('external_services', ['shortname' => 'adler_admin_service']);
        if (!$service) {
            $service = (object)[
                'name' => 'Adler Admin Service',
                'shortname' => 'adler_admin_service',
                'enabled' => 1,
                'restrictedusers' => 0,
                'downloadfiles' => 0,
                'uploadfiles' => 0
            ];
            $service->id = $DB->insert_record('external_services', $service);
        }

        // Create an external token for the user
        $token_data = [
            'token' => 'abc123xyz',
            'userid' => $user->id,
            'externalserviceid' => $service->id,
            'contextid' => 1,
            'creatorid' => 2,
            'iprestriction' => null,
            'validuntil' => null,
            'timecreated' => time(),
            'tokentype' => EXTERNAL_TOKEN_PERMANENT
        ];
        $token_id = $DB->insert_record('external_tokens', (object)$token_data);

        // Call the function
        $result = $moodle_core_repository->get_admin_services_site_admin_token($user->id);

        // Check the result
        $this->assertEquals($token_id, $result->id);
        $this->assertEquals('abc123xyz', $result->token);
        $this->assertEquals($user->id, $result->userid);
        $this->assertEquals($service->id, $result->externalserviceid);
        $this->assertEquals(EXTERNAL_TOKEN_PERMANENT, $result->tokentype);
    }
}