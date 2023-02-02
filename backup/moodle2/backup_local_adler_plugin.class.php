<?php
class backup_local_adler_plugin extends backup_local_plugin {
    /** Defines the structure of the backup file when backing up an instance of the local Adler plugin.
     *
     * @return backup_plugin_element
     * @throws backup_step_exception
     * @throws base_element_struct_exception
     */
    protected function define_module_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element();

        // To know if we are including userinfo
//        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Moodle does not allow names in nested elements that are used in the root element, therefore "score" is not allowed
        $score_item = new backup_nested_element("adler_score", null, array(
            'score_max',
            'timecreated',
            'timemodified',
        ));

        // Build the tree
        $plugin->add_child($pluginwrapper);

        $pluginwrapper->add_child($score_item);

        // Define sources
        $score_item->set_source_table('local_adler_scores_items', array('cmid' => backup::VAR_MODID));

        // Define id annotations

        // Define file annotations

        // Return the root element (choice), wrapped into standard activity structure
        return $plugin;
    }
}
