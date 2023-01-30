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
            new restore_path_element('points', $this->get_pathfor('/points'))
        ];
    }

    /** Processes a score item record during the restore process.
     *
     * @param object|array $data The data for the score item. Should be of type object, but is sometimes array (moodle logic).
     * @return void
     * @throws dml_exception
     */
    public function process_points($data) {
        global $DB;

        // Cast $data to object if it is an array
        // This is done because the object can sometimes randomly be an array
        $data = (object)$data;

        $cmid = $this->task->get_moduleid();
        $data->cmid = $cmid;

        // TODO check if completion is enabled (for this cm)

        // Insert the record into the database
        $DB->insert_record('local_adler_scores_items', $data);
    }
}
