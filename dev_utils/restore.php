<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

$categoryid         = 1; // e.g. 1 == Miscellaneous
$userdoingrestore   = 2; // e.g. 2 == admin
$courseid           = restore_dbops::create_new_course('', '', $categoryid);


$foldername = restore_controller::get_tempdir_name($courseid, $userdoingrestore);
$fp = get_file_packer('application/vnd.moodle.backup');
$tempdir = make_backup_temp_directory($foldername);
//$files = $fp->extract_to_pathname('/mnt/c/Users/heckmarm/Downloads/SoftwareEngineeringNoWordSearch.mbz', $tempdir);
$files = $fp->extract_to_pathname('/mnt/c/Users/heckmarm/Downloads/230404_Plugin.mbz', $tempdir);
//$files = $fp->extract_to_pathname('/home/markus/2023-02-14_example_course_with_adler_score_and_moodle_course_attr.mbz', $tempdir);
//$files = $fp->extract_to_pathname('/var/www/moodledata/filedir/1d/1e/1d1e42271545054e556dbfd41029ccf3a0c23a02', $tempdir);

$controller = new restore_controller(
    $foldername,
    $courseid,
    backup::INTERACTIVE_NO,
    backup::MODE_GENERAL,
    $userdoingrestore,
    backup::TARGET_NEW_COURSE
);
$controller->execute_precheck();
$controller->execute_plan();
$controller->destroy();

# php .\admin\cli\restore_backup.php --file="C:\Users\heckmarm\Downloads\SoftwareEngineeringNoWordSearch.mbz" --categoryid=1
