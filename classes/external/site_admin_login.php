<?php

namespace local_adler\external;

use context_system;
use core\di;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\util;
use dml_exception;
use invalid_parameter_exception;
use local_adler\local\db\moodle_core_repository;
use moodle_exception;

class site_admin_login extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'username' => new external_value(
                    PARAM_TEXT,
                    'username',
                    VALUE_REQUIRED
                ),
                'password' => new external_value(
                    PARAM_TEXT,
                    'password',
                    VALUE_REQUIRED
                )
            )
        );
    }

    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'token' => new external_value(
                PARAM_TEXT,
                'token',
                VALUE_REQUIRED
            )
        ]);
    }

    /**
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function execute($username, $password) {
        global $CFG;

        require_once($CFG->libdir . '/moodlelib.php');

        $user = authenticate_user_login($username, $password);

        if (!$user) {
            throw new invalid_parameter_exception('Invalid username or password');
        }

        if (!is_siteadmin($user->id)) {
            throw new invalid_parameter_exception('User is not a site admin');
        }

        try {
            $token = di::get(moodle_core_repository::class)
                ->get_admin_services_site_admin_token($user->id)
                ->token;
        } catch (dml_exception $e) {
            $token = util::generate_token(
                EXTERNAL_TOKEN_PERMANENT,
                util::get_service_by_name('adler_admin_service'),
                $user->id,
                context_system::instance(),
                time() + 86400,
                ''
            );
        }

        return ['token' => $token];
    }
}