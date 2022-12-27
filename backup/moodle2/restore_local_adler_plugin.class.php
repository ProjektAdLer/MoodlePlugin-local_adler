<?php
defined('MOODLE_INTERNAL') || die();

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
            new restore_path_element('score_item', $this->get_pathfor('/score_items/score_item'))
        ];
    }

    /** Processes a score item record during the restore process.
     *
     * @param object $data The data for the score item.
     * @return void
     * @throws dml_exception
     */
    public function process_score_item(object $data) {
        global $DB;

        // Cast $data to object if it is an array
        // This is done because the object can sometimes randomly be an array
        $data = (object)$data;

        $cmid = $this->task->get_moduleid();
        $data->course_modules_id = $cmid;

        // Insert the record into the database
        $DB->insert_record('local_adler_scores_items', $data);
    }
}