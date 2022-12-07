<?php

/**
 * @package local_adler
 * @author Markus
 * @license None
 */

//function local_adler_before_footer() {
//    \core\notification::add('test', \core\output\notification::NOTIFY_INFO);
//}

function local_adler_supports($feature) {
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}
