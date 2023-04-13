<?php

defined('MOODLE_INTERNAL') || die();


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
    ),
    array(
        'eventname'   => '\core\event\course_section_deleted',
        'callback'    => '\local_adler\observer::course_section_deleted',
    )
);