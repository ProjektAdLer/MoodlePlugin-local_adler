<?php
/**
 * Define all the backup steps that will be used by the backup_choice_activity_task
 */

class backup_local_adler_plugin extends backup_activity_structure_step {

    protected function define_my_settings() {
        // No particular settings for this activity
    }
    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        // Build the tree

        // Define sources

        // Define id annotations

        // Define file annotations

        // Return the root element (choice), wrapped into standard activity structure

    }

    protected function define_h5pactivity_plugin_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        // Build the tree

        // Define sources

        // Define id annotations

        // Define file annotations

        // Return the root element (choice), wrapped into standard activity structure

    }
}