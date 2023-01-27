<?php

define('CLI_SCRIPT', true);

require_once('config.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

$categoryid         = 1; // e.g. 1 == Miscellaneous
$userdoingrestore   = 2; // e.g. 2 == admin
$courseid           = restore_dbops::create_new_course('', '', $categoryid);


$foldername = restore_controller::get_tempdir_name($courseid, $userdoingrestore);
$fp = get_file_packer('application/vnd.moodle.backup');
$tempdir = make_backup_temp_directory($foldername);
$files = $fp->extract_to_pathname('/mnt/c/Users/heckmarm/Downloads/SoftwareEngineeringNoWordSearch.mbz', $tempdir);
//$files = $fp->extract_to_pathname('C:\Users\heckmarm\code\phpstorm\moodledata\filedir\f2\03\f2037361cea2c4c54643a482dc46f7fb4a6ac579', $tempdir);
//$files = $fp->extract_to_pathname('/mnt/c/Users/heckmarm/code/phpstorm/moodledata/filedir/2a/a3/2aa3e54e638356b83e970c4bf4fb94a068ba5083', $tempdir);
//$files = $fp->extract_to_pathname('/var/www/moodledata/filedir/37/5d/375dc41039a067d7c8f6e416afc33d38ef228bf8', $tempdir);

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
