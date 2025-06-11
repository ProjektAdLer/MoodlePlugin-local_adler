<?php

namespace local_adler\external;

use context_system;
use core\di;
use core\exception\invalid_parameter_exception;
use core\exception\moodle_exception;
use core_external\external_api;
use core_external\restricted_context_exception;
use core_user_external;
use dml_exception;
use dml_transaction_exception;
use moodle_database;
use core_user;

global $CFG;
require_once($CFG->dirroot . '/user/externallib.php');
require_once($CFG->dirroot . '/user/lib.php');

class create_users extends external_api {

    /**
     * Returns description of method parameters - exactly the same as core endpoint
     */
    public static function execute_parameters() {
        return core_user_external::create_users_parameters();
    }

    /**
     * Create users with bypassing password policy
     * @param $users
     * @return array
     * @throws restricted_context_exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function execute($users) {
//        global $CFG;
//        require_once($CFG->dirroot . '/user/lib.php');

        // Validate using the same parameters as core
        $params = self::validate_parameters(self::execute_parameters(), array('users' => $users));

        // Ensure the current user is allowed to run this function
        $context = context_system::instance();
        self::validate_context($context);

        // Enhanced admin-only check - ensure user is a site administrator
        if (!is_siteadmin()) {
            throw new moodle_exception('accessdenied', 'admin');
        }

        $transaction = di::get(moodle_database::class)->start_delegated_transaction();
        $original_passwords = array();
//        $original_auth_plugins = array();

        foreach ($params['users'] as $key => $user) {
            // Store the original password before modifying
            if (isset($user['password'])) {
                $original_passwords[$key] = $user['password'];

                // Replace with a random strong password that will pass policy checks
                $params['users'][$key]['password'] = generate_password(20);

                // should be used if db transaction cannot be used
//                $original_auth_plugins[$key] = isset($user['auth']) ? $user['auth'] : null;
//                $params['users'][$key]['auth'] = 'nologin';
            }

            // Or alternatively set createpassword to true (creates a random password and emails it)
            // $params['users'][$key]['createpassword'] = true;
        }

        // Call the core API
        $created_users = core_user_external::create_users($params['users']);

        // Now update the passwords directly in the database for each user
        foreach ($created_users as $user) {
            $user = core_user::get_user($user['id']);
            if (!empty($original_passwords[$key])) {
                // Update password without policy validation
                update_internal_user_password($user, $original_passwords[$key]);
            }
        }

        $transaction->allow_commit();
        return $created_users;
    }

    /**
     * Returns description of method result value
     */
    public static function execute_returns() {
        return core_user_external::create_users_returns();
    }
}