<?php
/**
 * Restoring logic for the local Adler plugin.
 */
class restore_local_adler_plugin extends restore_local_plugin {
    protected $db;

    public function __construct($name, $plugin, $restore) {
        global $DB;
        parent::__construct($name, $plugin, $restore);
        $this->db = $DB;
    }

    /** Defines the structure of the backup file when backing up an instance of the local Adler plugin.
     *
     * @return restore_path_element[]
     */
    protected function define_module_plugin_structure(): array {
        return [
            new restore_path_element('adler_module', $this->get_pathfor('/adler_module'))
        ];
    }

    /** Processes a score item record during the restore process.
     *
     * @param object|array $data The data for the score item. Should be of type object, but is sometimes array (moodle logic).
     * @return void
     * @throws dml_exception
     */
    public function process_adler_score($data) {
        global $DB;

        // Cast $data to object if it is an array
        // This is required because the object can sometimes randomly be an array
        $data = (object)$data;

        $cmid = $this->task->get_moduleid();
        $data->cmid = $cmid;

        // default values for timecreated and timemodified, if they are not set
        if (!isset($data->timecreated)) {
            $data->timecreated = time();
        }
        if (!isset($data->timemodified)) {
            $data->timemodified = time();
        }

        // The information whether availability is enabled or not is not (easily) available here -> not checking for it

        // Insert the record into the database
        $DB->insert_record('local_adler_course_modules', $data);
    }

    protected function define_section_plugin_structure(): array {
        return [
            new restore_path_element('adler_section', $this->get_pathfor('/adler_section'))
        ];
    }

    /**
     * @throws dml_exception
     */
    public function process_adler_section($data) {
        // Cast $data to object if it is an array
        // This is required because the object can sometimes randomly be an array
        $data = (object)$data;

        $data->section_id = $this->task->get_sectionid();

        // default values for timecreated and timemodified, if they are not set
        if (!isset($data->timecreated)) {
            $data->timecreated = time();
        }
        if (!isset($data->timemodified)) {
            $data->timemodified = time();
        }

        // Insert the record into the database
        $this->db->insert_record('local_adler_sections', $data);
    }

    protected function define_course_plugin_structure(): array {
        return [
            new restore_path_element('adler_course', $this->get_pathfor('/adler_course'))
        ];
    }

    /**
     * @throws dml_exception
     */
    public function process_adler_course($data) {
        // $data contains a dummy field "foo".
        // It is required because otherwise moodle thinks there is nothing to restore and skips the restore.
        // It is ignored by insert_record.
        global $DB;

        // Cast $data to object if it is an array
        // This is required because the object can sometimes randomly be an array
        $data = (object)$data;

        // default values for timecreated and timemodified, if they are not set
        if (!isset($data->timecreated)) {
            $data->timecreated = time();
        }
        if (!isset($data->timemodified)) {
            $data->timemodified = time();
        }

        $data->course_id = $this->task->get_courseid();

        // Insert the record into the database
        $DB->insert_record('local_adler_course', $data);
    }
}

