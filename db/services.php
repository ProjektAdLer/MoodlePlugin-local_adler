<?php

// TODO: 'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),  only for debugging/testing purposes
// TODO: remove not required options

$services = array(
    'adler_services' => array(                                                // the name of the web service
        'functions' => array ('local_adler_score_primitive_learning_element', 'local_adler_score_h5p_learning_element', 'local_adler_score_get_element_scores', 'local_adler_score_get_course_scores'), // web service functions of this service
        'requiredcapability' => '',                // if set, the web service user need this capability to access
        // any function of this service. For example: 'some/capability:specified'
        'restrictedusers' => 1,                                             // if enabled, the Moodle administrator must link some user to this service
        // into the administration
        'enabled' => 1,                                                       // if enabled, the service can be reachable on a default installation
        'shortname' =>  'adler_services',       // optional – but needed if restrictedusers is set so as to allow logins.
        'downloadfiles' => 0,    // allow file downloads.
        'uploadfiles'  => 0,      // allow file uploads.
        'loginrequired' => true
    )
);

$functions = array(
    'local_adler_score_primitive_learning_element' => array(         //web service function name
        'classname'   => 'local_adler_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'score_primitive_learning_element',          //external function name
        'classpath'   => 'local/adler/externallib.php',  //file containing the class/external function - not required if using namespaced auto-loading classes.
        // defaults to the service's externalib.php
        'description' => 'Submit result for primitive learning elements (completed/not completed)',    //human readable description of the web service function
        'type'        => 'write',                  //database rights of the web service function (read, write)
        'ajax' => false,        // is the service available to 'internal' ajax calls.
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),   // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
        'loginrequired' => true
    ),
    'local_adler_score_h5p_learning_element' => array(
        'classname'   => 'local_adler_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'score_h5p_learning_element',          //external function name
        'classpath'   => 'local/adler/externallib.php',  //file containing the class/external function - not required if using namespaced auto-loading classes.
        // defaults to the service's externalib.php
        'description' => 'Submit result for h5p. This is just a proxy function and forwards its payload to {"wsfunction", "core_xapi_statement_post"}, {"component", "mod_h5pactivity"}, {"requestjson", "[" + statement + "]"}',    //human readable description of the web service function
        'type'        => 'write',                  //database rights of the web service function (read, write)
        'ajax' => false,        // is the service available to 'internal' ajax calls.
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),   // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
        'loginrequired' => true
    ),
    'local_adler_score_get_element_scores' => array(
        'classname'   => 'local_adler_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'score_get_element_scores',          //external function name
        'classpath'   => 'local/adler/externallib.php',  //file containing the class/external function - not required if using namespaced auto-loading classes.
        // defaults to the service's externalib.php
        'description' => 'Get scores (DSL) for elements with given ids',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => false,        // is the service available to 'internal' ajax calls.
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),   // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
        'loginrequired' => true
    ),
    'local_adler_score_get_course_scores' => array(
        'classname'   => 'local_adler_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'score_get_course_scores',          //external function name
        'classpath'   => 'local/adler/externallib.php',  //file containing the class/external function - not required if using namespaced auto-loading classes.
        // defaults to the service's externalib.php
        'description' => 'Get scores (DSL) for all elements inside course with given course id',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => false,        // is the service available to 'internal' ajax calls.
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),   // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
        'loginrequired' => true
    )
);
















