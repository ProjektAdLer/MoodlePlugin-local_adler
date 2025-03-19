<?php

namespace local_adler\external;

use context_system;
use core\di;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\restricted_context_exception;
use core_user_external;
use dml_exception;
use invalid_parameter_exception;
use local_adler\local\db\moodle_core_repository;
use local_declarativesetup\local\play\course_category\util\course_category_path;
use moodle_exception;

global $CFG;
require_once($CFG->dirroot . '/user/externallib.php');
require_once($CFG->dirroot . '/user/lib.php');

class get_users_with_category_roles extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'roles' => new external_value(
                PARAM_TEXT,
                'comma seperated list of role shortnames to search for. Can be an empty string',
            ),
            'pagination' => new external_single_structure([
                'page' => new external_value(
                    PARAM_INT,
                    'Page number (starting from 0)',
                    VALUE_REQUIRED,
                ),
                'per_page' => new external_value(
                    PARAM_INT,
                    'Number of items per page',
                    VALUE_REQUIRED,
                )
            ])
        ]);
    }

    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'user' => core_user_external::user_description(),
                    'categories' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'category' => new external_value(
                                    PARAM_TEXT,
                                    'Category path',
                                    VALUE_REQUIRED
                                ),
                                'category_id' => new external_value(
                                    PARAM_INT,
                                    'Category ID',
                                    VALUE_REQUIRED
                                ),
                                'role' => new external_value(
                                    PARAM_TEXT,
                                    'Role shortname',
                                    VALUE_REQUIRED
                                )
                            ],
                            default: []
                        )
                    )
                ]),
            )
        ]);
    }

    /**
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function execute(string $roles, array $pagination): array {
        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), compact('roles', 'pagination'));
        $roles = empty($params['roles']) ? [] : array_map('trim', explode(',', $params['roles']));

        // Check permissions - require site admin capability
        $context = context_system::instance();
        self::validate_context($context);

        $result_data = [];

        // Get role IDs from names
        $roles = array_map(
            fn(string $role) => [
                'id' => di::get(moodle_core_repository::class)->get_role_id_by_shortname($role, MUST_EXIST),
                'shortname' => $role
            ],
            $roles);

        // iterate over all users
        $users = get_users(
            page: (string)($params['pagination']['page'] * $params['pagination']['per_page']),  # page is not page, it's the number of the first record on the page
            recordsperpage: (string)$params['pagination']['per_page']
        );
        foreach ($users as $user) {
            $categories = [];

            // iterate over all roles
            foreach ($roles as $role) {
                $categories_with_role = di::get(moodle_core_repository::class)->get_category_ids_where_user_has_role(
                    $user->id,
                    $role['id'],
                    CONTEXT_COURSECAT
                );

                foreach ($categories_with_role as $category_with_role) {
                    $ccp = course_category_path::from_category_id($category_with_role);

                    $categories[] = [
                        'category' => (string)$ccp,
                        'category_id' => $ccp->get_category_id(),
                        'role' => $role['shortname']
                    ];
                }
            }

            $result_data[] = [
                'user' => user_get_user_details_courses($user),
                'categories' => $categories
            ];
        }

        return ['data' => $result_data];
    }
}
