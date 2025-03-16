<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adler\external;

use local_adler\lib\adler_externallib_testcase;
use invalid_parameter_exception;

global $CFG;
require_once($CFG->dirroot . '/local/adler/tests/lib/adler_testcase.php');

class site_admin_login_test extends adler_externallib_testcase {
    public function test_execute_parameters() {
        $params = site_admin_login::execute_parameters();
        $this->assertInstanceOf('core_external\external_function_parameters', $params);
        $this->assertEmpty($params->keys);
    }

    public static function returns_data_provider(): array {
        return [
            'standard token' => [
                [
                    'token' => 'abc123xyz789'
                ]
            ],
            'long token' => [
                [
                    'token' => str_repeat('a', 128)
                ]
            ],
            'empty token' => [
                [
                    'token' => ''
                ]
            ]
        ];
    }

    /**
     * @dataProvider returns_data_provider
     */
    public function test_execute_returns(array $data) {
        site_admin_login::validate_parameters(site_admin_login::execute_returns(), $data);
        $this->assertTrue(true);
    }

    public function test_execute_with_non_admin_user() {
        // Create a regular user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('User is not a site admin');

        $result = site_admin_login::execute();
    }

    public function test_execute_success() {
        global $DB, $USER;

        // Create an admin user
        $this->setAdminUser();

        // Create the external service if it doesn't exist
        $service = $DB->get_record('external_services', ['shortname' => 'adler_admin_service']);
        if (!$service) {
            $service = (object)[
                'name' => 'Adler Admin Service',
                'shortname' => 'adler_admin_service',
                'enabled' => 1,
                'restrictedusers' => 0,
                'downloadfiles' => 0,
                'uploadfiles' => 0,
                'timecreated' => time()
            ];
            $service->id = $DB->insert_record('external_services', $service);
        }

        // Execute the function with admin user already set
        $result = site_admin_login::execute();

        // Verify a token was returned
        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);

        // Verify token exists in the database
        $token_exists = $DB->record_exists('external_tokens', [
            'token' => $result['token'],
            'userid' => $USER->id,
            'externalserviceid' => $service->id
        ]);
        $this->assertTrue($token_exists);
    }

    public function test_execute_reuses_existing_token() {
        global $DB, $USER;

        // Create an admin user
        $this->setAdminUser();

        // Create the external service if it doesn't exist
        $service = $DB->get_record('external_services', ['shortname' => 'adler_admin_service']);
        if (!$service) {
            $service = (object)[
                'name' => 'Adler Admin Service',
                'shortname' => 'adler_admin_service',
                'enabled' => 1,
                'restrictedusers' => 0,
                'downloadfiles' => 0,
                'uploadfiles' => 0,
                'timecreated' => time()
            ];
            $service->id = $DB->insert_record('external_services', $service);
        }

        // Create an existing token
        $expected_token = 'existing_admin_token_' . time();
        $token_data = [
            'token' => $expected_token,
            'userid' => $USER->id,
            'externalserviceid' => $service->id,
            'contextid' => 1,
            'creatorid' => 2,
            'iprestriction' => null,
            'validuntil' => null,
            'timecreated' => time(),
            'tokentype' => EXTERNAL_TOKEN_PERMANENT
        ];
        $DB->insert_record('external_tokens', (object)$token_data);

        // Execute the login function
        $result = site_admin_login::execute();

        // Should return the existing token
        $this->assertEquals(['token' => $expected_token], $result);
    }
}