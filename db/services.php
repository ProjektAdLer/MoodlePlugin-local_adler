<?php
defined('MOODLE_INTERNAL') || die();

$services = array(
    'adler_services' => array(// the name of the web service
        'functions' => array(
            'core_course_get_contents',
            'core_course_search_courses',
            'core_webservice_get_site_info',
            'core_user_get_users_by_field',
            'core_course_delete_courses',
            'tool_dataprivacy_create_data_request',
            'tool_dataprivacy_get_data_requests',
            'tool_dataprivacy_get_data_request'
        ), // web service functions of this service
        'requiredcapability' => '',                // if set, the web service user need this capability to access
        // any function of this service. For example: 'some/capability:specified'
        'restrictedusers' => 0,                                             // if enabled, the Moodle administrator must link some user to this service
        // into the administration
        'enabled' => 1,                                                       // if enabled, the service can be reachable on a default installation
        'shortname' => 'adler_services',       // optional – but needed if restrictedusers is set so as to allow logins.
        'downloadfiles' => 1,    // allow file downloads.
        'uploadfiles' => 0,      // allow file uploads.
        'loginrequired' => true
    ),
    'adler_admin_service' => array(
        'functions' => array(
            'local_declarativesetup_create_course_cat_and_assign_user_role',
            'core_user_create_users',
//            'core_course_create_categories',
//            'core_role_assign_roles',
            'core_user_delete_users',
//            'core_user_get_users',
            'enrol_self_enrol_user',
            'enrol_manual_enrol_users',
            'core_course_get_courses',
            'core_enrol_get_enrolled_users',
            'core_course_get_categories',
            'core_course_delete_categories'
        ),
        'requiredcapability' => 'moodle/site:config',  // not exactly the same as is_siteadmin() but as close as we can get "This capability is intended for administrators only. It is not set for any of the default roles."
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'adler_admin_service',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
        'loginrequired' => true
    )
);

$functions = array(
    'local_adler_site_admin_login' => [
        'classname' => 'local_adler\external\site_admin_login',
        'description' => 'Login as site admin and get token',
        'type' => 'write',
        'ajax' => false,
        'services' => array('adler_admin_service'),
        'capabilities' => '',
        'loginrequired' => true
    ],
    'local_adler_get_users_with_category_roles' => [
        'classname' => 'local_adler\external\get_users_with_category_roles',
        'description' => 'Get all users with a list of categories the user has the asked roles in',
        'type' => 'read',
        'ajax' => false,
        'services' => array('adler_admin_service'),
        'capabilities' => 'moodle/site:config, moodle/user:viewdetails',
        'loginrequired' => true
    ],

    'local_adler_trigger_event_cm_viewed' => [         //web service function name
        'classname' => 'local_adler\external\trigger_event_cm_viewed',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'description' => 'Marks the element as viewed by triggering the "viewed" event for the cm.',    //human readable description of the web service function
        'type' => 'write',                  //database rights of the web service function (read, write)
        'ajax' => false,        // is the service available to 'internal' ajax calls.
        'services' => array('adler_services'),   // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
        'loginrequired' => true
    ],
    'local_adler_score_h5p_learning_element' => array(
        'classname' => 'local_adler\external\score_h5p_learning_element',
        'description' => 'Submit result for h5p. This is just a proxy function and forwards its payload to {"wsfunction", "core_xapi_statement_post"}, {"component", "mod_h5pactivity"}, {"requestjson", "[" + statement + "]"}',    //human readable description of the web service function
        'type' => 'write',
        'ajax' => false,
        'services' => array('adler_services'),
        'capabilities' => '',
        'loginrequired' => true
    ),
    'local_adler_score_get_element_scores' => array(
        'classname' => 'local_adler\external\score_get_element_scores',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'description' => 'Get scores (adler) for learning elements with given ids',
        'type' => 'read',                  //database rights of the web service function (read, write)
        'ajax' => false,
        'services' => array('adler_services'),
        'capabilities' => '', // comma separated list of capabilities used by the function.
        'loginrequired' => true
    ),
    'local_adler_score_get_course_scores' => array(
        'classname' => 'local_adler\external\score_get_course_scores',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'description' => 'Get scores (adler) for all elements inside course with given course id',    //human readable description of the web service function
        'type' => 'read',                  //database rights of the web service function (read, write)
        'ajax' => false,        // is the service available to 'internal' ajax calls.
        'services' => array('adler_services'),   // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
        'loginrequired' => true
    ),
    'local_adler_upload_course' => array(
        'classname' => 'local_adler\external\upload_course',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'description' => 'Upload adler course (as mbz file)',    //human readable description of the web service function
        'type' => 'write',                  //database rights of the web service function (read, write)
        'ajax' => false,        // is the service available to 'internal' ajax calls.
        'services' => array('adler_services'),   // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
        'loginrequired' => true
    ),
    'local_adler_get_element_ids_by_uuids' => array(
        'classname' => 'local_adler\external\get_element_ids_by_uuids',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'description' => 'Returns context and database ids for sections / course modules with given UUIDs and course ids',    //human readable description of the web service function
        'type' => 'read',                  //database rights of the web service function (read, write)
        'ajax' => false,        // is the service available to 'internal' ajax calls.
        'services' => array('adler_services'),   // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
        'loginrequired' => true
    ),
);