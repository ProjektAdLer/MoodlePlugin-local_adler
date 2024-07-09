<?php

define('CLI_SCRIPT', 1);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

if ($argc < 2) {
    echo "Usage: php backup_script.php <course_to_backup>\n";
    exit(1);
}

$course_to_backup = $argv[1]; // Set this to one existing choice cmid in your dev site
$user_doing_the_backup = 2; // Set this to the id of your admin account

$bc = new backup_controller(backup::TYPE_1COURSE, $course_to_backup, backup::FORMAT_MOODLE,
    backup::INTERACTIVE_NO, backup::MODE_GENERAL, $user_doing_the_backup);
$bc->execute_plan();

// output file path of $file
$file = $bc->get_results();
$file = reset($file);
$filepath = $file->get_contenthash();
$filepath = $CFG->dataroot . '/filedir/' . substr($filepath, 0, 2) . '/' . substr($filepath, 2, 2) . '/' . $filepath;
// $this->bc->get_results()['backup_destination']->copy_content_to_temp()
echo "Backup file path:\n";
echo $filepath . "\n";

return $filepath;
