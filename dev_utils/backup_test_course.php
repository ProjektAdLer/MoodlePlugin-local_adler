<?php

define('CLI_SCRIPT', 1);
require_once('config.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');



$course_to_backup = 29; // Set this to one existing choice cmid in your dev site
$user_doing_the_backup   = 2; // Set this to the id of your admin account

$bc = new backup_controller(backup::TYPE_1COURSE, $course_to_backup, backup::FORMAT_MOODLE,
    backup::INTERACTIVE_NO, backup::MODE_GENERAL, $user_doing_the_backup);
$bc->execute_plan();

// output file path of $file
$file = $bc->get_results();
$file = reset($file);
$filepath = $file->get_contenthash();
$filepath = $CFG->dataroot . '/filedir/' . substr($filepath, 0, 2) . '/' . substr($filepath, 2, 2) . '/' . $filepath;
echo "Backup file path:\n";
echo $filepath;


