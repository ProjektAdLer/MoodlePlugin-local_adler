<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');


if ($argc < 2) {
    echo "Usage: php script.php <filepath>\n";
    exit(1);
}

$filepath = $argv[1];


$categoryid         = 1; // e.g. 1 == Miscellaneous
$userdoingrestore   = 2; // e.g. 2 == admin
$courseid           = restore_dbops::create_new_course('', '', $categoryid);

$foldername = restore_controller::get_tempdir_name($courseid, $userdoingrestore);
$fp = get_file_packer('application/vnd.moodle.backup');
$tempdir = make_backup_temp_directory($foldername);

$files = $fp->extract_to_pathname($filepath, $tempdir);

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
