<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler\external;

use context_coursecat;
use local_adler\lib\adler_externallib_testcase;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class get_users_with_category_roles_test extends adler_externallib_testcase {
    public function test_execute_parameters() {
        $params = get_users_with_category_roles::execute_parameters();
        $this->assertInstanceOf('core_external\external_function_parameters', $params);
        $this->assertArrayHasKey('roles', $params->keys);
        $this->assertArrayHasKey('pagination', $params->keys);
    }

    public function test_execute_returns() {
        $returns = get_users_with_category_roles::execute_returns();
        $this->assertInstanceOf('core_external\external_function_parameters', $returns);
        $this->assertArrayHasKey('data', $returns->keys);
    }

    public function test_execute_empty_roles() {
        // Create an admin user
        $this->setAdminUser();

        // Include the pagination parameter
        $result = get_users_with_category_roles::execute('', ['page' => 0, 'per_page' => 10]);

        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    public function test_execute_pagination() {
        global $DB, $CFG;

        // Create an admin user
        $this->setAdminUser();

        // Create several users to test pagination
        for ($i = 0; $i < 10; $i++) {
            $this->getDataGenerator()->create_user();
        }
        $total_user_count = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {user} WHERE id <> :guestid AND deleted = 0",
            ['guestid' => $CFG->siteguest]
        );
        $per_page = $total_user_count - 2;


        // Test first page with 5 users per page
        $result1 = get_users_with_category_roles::execute('', ['page' => 0, 'per_page' => $per_page]);
        $this->assertCount($per_page, $result1['data']);

        // Test second page with 5 users per page
        $result2 = get_users_with_category_roles::execute('', ['page' => 1, 'per_page' => $per_page]);
        $this->assertCount($total_user_count - $per_page, $result2['data']);

        // Ensure different sets of users were returned
        $ids_page1 = array_map(function ($item) {
            return $item['user']['id'];
        }, $result1['data']);

        $ids_page2 = array_map(function ($item) {
            return $item['user']['id'];
        }, $result2['data']);

        $this->assertEmpty(array_intersect($ids_page1, $ids_page2));

        // Verify total count across both pages matches database count
        $this->assertEquals(
            $total_user_count,
            count($ids_page1) + count($ids_page2),
            'Total users returned across pages should match total users in database'
        );
    }

    public function test_execute_with_users_having_roles() {
        global $DB;

        // Create test users
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create test categories
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        // Set up roles
        $teacher_role_id = $DB->get_field('role', 'id', ['shortname' => 'teacher'], MUST_EXIST);
        $manager_role_id = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);

        // Assign roles to users
        $context1 = context_coursecat::instance($category1->id);
        $context2 = context_coursecat::instance($category2->id);
        role_assign($teacher_role_id, $user1->id, $context1->id);
        role_assign($manager_role_id, $user2->id, $context2->id);

        // Set admin user and execute with pagination
        $this->setAdminUser();
        $result = get_users_with_category_roles::execute(
            'teacher,manager',
            ['page' => 0, 'per_page' => 50]
        );

        // Assertions
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);

        // Find the test users in the results
        $user1_data = null;
        $user2_data = null;
        foreach ($result['data'] as $user_data) {
            if ($user_data['user']['id'] == $user1->id) {
                $user1_data = $user_data;
            } else if ($user_data['user']['id'] == $user2->id) {
                $user2_data = $user_data;
            }
        }

        // Verify user1 has teacher role in category1
        $this->assertNotNull($user1_data, 'User1 was not found in the results');
        $this->assertArrayHasKey('categories', $user1_data);

        // Category path should contain the category name
        $category1_path = $category1->name;

        // Find category1 in user1's categories
        $found_category1 = false;
        foreach ($user1_data['categories'] as $category) {
            if ($category['category'] == $category1_path && $category['role'] == 'teacher') {
                $found_category1 = true;
                break;
            }
        }
        $this->assertTrue($found_category1, 'User1 should have teacher role in category1');

        // Verify user2 has manager role in category2
        $this->assertNotNull($user2_data, 'User2 was not found in the results');
        $this->assertArrayHasKey('categories', $user2_data);

        // Category path should contain the category name
        $category2_path = $category2->name;

        // Find category2 in user2's categories
        $found_category2 = false;
        foreach ($user2_data['categories'] as $category) {
            if ($category['category'] == $category2_path && $category['role'] == 'manager') {
                $found_category2 = true;
                break;
            }
        }
        $this->assertTrue($found_category2, 'User2 should have manager role in category2');
    }

    public function test_execute_with_user_no_roles() {
        // Create an admin user
        $this->setAdminUser();

        // Create a user with no roles
        $user = $this->getDataGenerator()->create_user();

        // Include pagination parameter
        $result = get_users_with_category_roles::execute(
            'teacher',
            ['page' => 0, 'per_page' => 100]
        );

        // Find this user in the results
        $user_data = null;
        foreach ($result['data'] as $data) {
            if ($data['user']['id'] == $user->id) {
                $user_data = $data;
                break;
            }
        }

        $this->assertNotNull($user_data);
        $this->assertArrayHasKey('categories', $user_data);
        $this->assertEmpty($user_data['categories']);
    }
}