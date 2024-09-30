<?php

class backup_local_adler_plugin extends backup_local_plugin {
    /**
     * @throws base_element_struct_exception
     */
protected function define_course_plugin_structure(): backup_plugin_element {
    $plugin = $this->get_plugin_element();

    // Define each element separated
    $pluginwrapper = new backup_nested_element($this->get_recommended_name(), null, [
        'plugin_release_set_version',
    ]);

    // Moodle does not allow names in nested elements that are used in the root element, therefore "score" is not allowed
    $adler_course = new backup_nested_element("adler_course", null, [
        'uuid',
    ]);

    // Build the tree
    $plugin->add_child($pluginwrapper);
    $pluginwrapper->add_child($adler_course);

    // Define sources
    // version number according to https://github.com/ProjektAdLer/moodle-docker/blob/main/plugin-releases.json
    $pluginwrapper->set_source_array([['plugin_release_set_version' => '4.0.0']]);
    $adler_course->set_source_table('local_adler_course', array('course_id' => backup::VAR_COURSEID));
    // Define id annotations

    // Define file annotations

    // Return the root element, wrapped into standard activity structure
    return $plugin;
}
    protected function define_section_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element();

        // To know if we are including userinfo

        // Define each element separated
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        $adler_section = new backup_nested_element("adler_section", null, [
            'uuid',
            'required_points_to_complete'
        ]);

        // Build the tree
        $plugin->add_child($pluginwrapper);
        $pluginwrapper->add_child($adler_section);

        // Define sources
        $adler_section->set_source_table('local_adler_sections', array('section_id' => backup::VAR_SECTIONID));

        // Define id annotations

        // Define file annotations

        // Return the root element, wrapped into standard activity structure
        return $plugin;
    }

    /** Defines the structure of the backup file when backing up an instance of the local Adler plugin.
     *
     * @return backup_plugin_element
     * @throws base_element_struct_exception
     */
    protected function define_module_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element();

        // To know if we are including userinfo
//        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $pluginwrapper = new backup_nested_element($this->get_recommended_name(), );

        // Moodle does not allow names in nested elements that are used in the root element, therefore "score" is not allowed
        $score_item = new backup_nested_element("adler_module", null, array(
            'score_max',
            'uuid',
            'timecreated',
            'timemodified',
        ));

        // Build the tree
        $plugin->add_child($pluginwrapper);

        $pluginwrapper->add_child($score_item);

        // Define sources
        $score_item->set_source_table('local_adler_course_modules', array('cmid' => backup::VAR_MODID));

        // Define id annotations

        // Define file annotations

        // Return the root element, wrapped into standard activity structure
        return $plugin;
    }
}
