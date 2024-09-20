<?php

use local_adler\local\exceptions\not_an_adler_course_exception;
use local_adler\local\upgrade\upgrade_3_2_0_to_4_0_0_completionlib;

/**
 * @throws moodle_exception
 */
function xmldb_local_adler_upgrade($oldversion): bool {
//    global $CFG, $DB;
//    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2024090900) {
        $success = true;
        try {
            $courses = get_courses('all');
            foreach ($courses as $course) {
                if ($course->id == 1) {
                    // moodle special course for startpage
                    continue;
                }
                try {
                    $upgrader = new upgrade_3_2_0_to_4_0_0_completionlib($course->id);
                    $upgrader->execute();
                } catch (not_an_adler_course_exception $e) {
                    // Do nothing
                }
            }
        } catch (moodle_exception $e) {
            $success = false;
//            throw $e;
        }

        // Logging the upgrade.
        upgrade_plugin_savepoint($success, 2024090900, 'local', 'adler');

    }

    // Everything has succeeded to here. Return true.
    return true;
}
