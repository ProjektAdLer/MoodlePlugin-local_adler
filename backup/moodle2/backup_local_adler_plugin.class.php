<?php

class backup_local_adler_plugin extends backup_local_plugin {
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element(null, null, null);
        $score_types = new backup_nested_element($this->get_recommended_name(), array('id'), array('name', 'timecreated', 'timemodified'));
        $plugin->add_child($score_types);
        $score_types->set_source_sql('SELECT * FROM {adler_scores_types}', array());
        return $plugin;
//        die('test');
    }

    protected function define_module_plugin_structure() {
        $plugin = $this->get_plugin_element(null, null, null);
        $score_types = new backup_nested_element($this->get_recommended_name(), array('id'), array('name', 'timecreated', 'timemodified'));
        $plugin->add_child($score_types);
        $score_types->set_source_sql('SELECT * FROM {adler_scores_types}', array());
        return $plugin;
    }
}