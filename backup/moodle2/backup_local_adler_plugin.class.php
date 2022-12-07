<?php

//require_once($CFG->dirroot . '/local/adler/backup/moodle2/backup_local_adler_stepslib.php'); // Because it exists (must)
//require_once($CFG->dirroot . '/local/adler/backup/moodle2/backup_local_adler_settingslib.php'); // Because it exists (optional)

/**
 * choice backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_local_adler_plugin extends backup_local_plugin {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

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



    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new backup_choice_activity_structure_step('local_adler_structure', 'adler.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        return $content;
    }
}