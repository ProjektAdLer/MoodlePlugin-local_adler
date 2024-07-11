<?php /** @noinspection PhpIllegalPsrClassPathInspection */

global $CFG;

use local_adler\lib\adler_testcase;
use local_adler\local\course_category_manager;
use local_adler\local\exceptions\exit_exception;

require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class create_course_cat_and_assign_user_role_test extends adler_testcase {
    /**
     * @dataProvider provide_test_create_course_cat_and_assign_user_role_data
     * @runInSeparateProcess
     *
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_create_course_cat_and_assign_user_role($username, $role, $category_path, $expected_parameters, $expected_exit_code) {
        global $CFG;

        // Arrange
        $mock = Mockery::mock('overload:' . course_category_manager::class);
        $mock->shouldReceive('create_category_user_can_create_courses_in')
            ->once()
            ->with(...$expected_parameters)
            ->andReturn(42);

        $_SERVER['argv'] = [
            'create_course_cat_and_assign_user_role.php',
            '--username=' . $username,
            '--role=' . $role,
            '--category_path=' . $category_path,
        ];


        if ($expected_exit_code !== 0) {
            $this->expectExceptionCode($expected_exit_code);
        }

        // Act
        require $CFG->dirroot . '/local/adler/cli/create_course_cat_and_assign_user_role.php';
    }

    public function provide_test_create_course_cat_and_assign_user_role_data() {
        return [
            [
                'username' => 'valid_username',
                'role' => 'valid_role',
                'category_path' => 'valid_category_path',
                'expected_parameters' => ['valid_username', 'valid_role', 'valid_category_path'],
                'expected_exit_code' => 0,
            ], [
                'username' => ' valid_username ',
                'role' => ' valid_role ',
                'category_path' => ' valid_category_path ',
                'expected_parameters' => ['valid_username', 'valid_role', 'valid_category_path'],
                'expected_exit_code' => 0,
            ], [
                'username' => 'valid_username',
                'role' => 'valid_role',
                'category_path' => null,
                'expected_parameters' => ['valid_username', 'valid_role', null],
                'expected_exit_code' => 0,
            ], [
                'username' => null,
                'role' => 'valid_role',
                'category_path' => 'valid_category_path',
                'expected_parameters' => [],
                'expected_exit_code' => 1,
            ], [
                'username' => '',
                'role' => 'valid_role',
                'category_path' => 'valid_category_path',
                'expected_parameters' => [],
                'expected_exit_code' => 1,
            ], [
                'username' => 'valid_username',
                'role' => null,
                'category_path' => 'valid_category_path',
                'expected_parameters' => [],
                'expected_exit_code' => 1,
            ],

        ];
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_script_with_unknown_parameter() {
        global $CFG;

        // Arrange
        $_SERVER['argv'] = [
            'create_course_cat_and_assign_user_role.php',
            '--unknown_parameter=value',
        ];

        $this->expectException(exit_exception::class);
        $this->expectExceptionCode(1);

        // Act
        require $CFG->dirroot . '/local/adler/cli/create_course_cat_and_assign_user_role.php';
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_script_with_help_parameter() {
        global $CFG;

        // Arrange
        $_SERVER['argv'] = [
            'create_course_cat_and_assign_user_role.php',
            '--help',
        ];

        // Act
        ob_start();
        require $CFG->dirroot . '/local/adler/cli/create_course_cat_and_assign_user_role.php';
        $output = ob_get_clean();

        // Assert
        $this->assertStringContainsString('username=STRING', $output);
    }

    /**
     * integration test
     *
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_script_with_valid_parameters() {
        $category_path = 'test / valid_category_path';

        global $CFG, $DB;

        // Arrange
        $user = $this->getDataGenerator()->create_user();
        $role_id = $this->getDataGenerator()->create_role();
        $role = $DB->get_record('role', ['id' => $role_id]);

        $_SERVER['argv'] = [
            'create_course_cat_and_assign_user_role.php',
            '--username=' . $user->username,
            '--role=' . $role->shortname,
            '--category_path=' . $category_path,
        ];

        // Act
        require $CFG->dirroot . '/local/adler/cli/create_course_cat_and_assign_user_role.php';

        // Assert
        // category exists
        $category_id = array_search($category_path, core_course_category::make_categories_list());
        $this->assertNotFalse($category_id);

        // user has the role
        // Check if user has role in category context
        $users_with_role = get_role_users($role_id, context_coursecat::instance($category_id));
        $this->assertArrayHasKey($user->id, $users_with_role);
    }

    /**
     * integration test
     *
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_script_without_category_path() {
        global $CFG, $DB;

        // Arrange
        $user = $this->getDataGenerator()->create_user();
        $role_id = $this->getDataGenerator()->create_role();
        $role = $DB->get_record('role', ['id' => $role_id]);

        $_SERVER['argv'] = [
            'create_course_cat_and_assign_user_role.php',
            '--username=' . $user->username,
            '--role=' . $role->shortname,
        ];

        $category_count_before = count(core_course_category::make_categories_list());

        // Act
        require $CFG->dirroot . '/local/adler/cli/create_course_cat_and_assign_user_role.php';

        // Assert
        // default creates two categories, "adler" and "adler / <username>"
        $this->assertEquals($category_count_before + 2, count(core_course_category::make_categories_list()));
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_with_role_that_cannot_be_assigned_to_course_category() {
        global $CFG, $DB;

        // Arrange
        $user = $this->getDataGenerator()->create_user();
        $role_id = $this->getDataGenerator()->create_role();
        $role = $DB->get_record('role', ['id' => $role_id]);
        $contextlevels = [
            CONTEXT_COURSE,
        ];
        set_role_contextlevels($role_id, $contextlevels);

        $_SERVER['argv'] = [
            'create_course_cat_and_assign_user_role.php',
            '--username=' . $user->username,
            '--role=' . $role->shortname,
        ];

        $this->expectException(exit_exception::class);
        $this->expectExceptionCode(1);

        // Act
        require $CFG->dirroot . '/local/adler/cli/create_course_cat_and_assign_user_role.php';
    }
}
