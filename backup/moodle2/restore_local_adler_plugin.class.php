<?php
/**
 * Restoring logic for the local Adler plugin.
 */
class restore_local_adler_plugin extends restore_local_plugin {
    /** Defines the structure of the backup file when backing up an instance of the local Adler plugin.
     *
     * @return restore_path_element[]
     */
    protected function define_module_plugin_structure(): array {
        return [
            new restore_path_element('adler_score', $this->get_pathfor('/adler_score'))
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
        // This is done because the object can sometimes randomly be an array
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
        $DB->insert_record('local_adler_scores_items', $data);
    }
}

