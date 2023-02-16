<?php

defined('MOODLE_INTERNAL') || die();
//use local_adler\local_adler_observer;

$observers = array(
    array(
        'eventname'   => '\core\event\course_module_deleted',
        'callback'    => '\local_adler\observer::course_module_deleted',
    ),
    array(
        'eventname'   => '\core\event\course_deleted',
        'callback'    => '\local_adler\observer::course_deleted',
    ),
    array(
        'eventname'   => '\core\event\course_content_deleted',
        'callback'    => '\local_adler\observer::course_content_deleted',
    )
);