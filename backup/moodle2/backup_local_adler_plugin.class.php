<?php

class backup_local_adler_plugin extends backup_local_plugin
{
//    protected function define_course_plugin_structure()
//    {
//        $plugin = $this->get_plugin_element(null, null, null);
//
//        // To know if we are including userinfo
//        // not applicable on course level
//
//        // Define each element separated
//        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
//
//        $score_types = new backup_nested_element("score_types");
//        $score_type = new backup_nested_element("score_type", array('id'), array('name', 'timecreated', 'timemodified'));
//
//        // Build the tree
//        $plugin->add_child($pluginwrapper);
//
//        $pluginwrapper->add_child($score_types);
//        $score_types->add_child($score_type);
//
//        // Define sources
//        $score_type->set_source_table("local_adler_scores_types", array('course_id' => backup::VAR_COURSEID));
////        $score_type->set_source_sql('SELECT * FROM {local_adler_scores_types} WHERE course_id = ?', array(backup::VAR_COURSEID));
//
//        // Define id annotations
//
//        // Define file annotations
//
//        // Return the root element (choice), wrapped into standard activity structure
//        return $plugin;
//    }

    protected function define_module_plugin_structure()
    {
        $plugin = $this->get_plugin_element(null, null, null);

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        $score_items = new backup_nested_element("score_items");
        $score_item = new backup_nested_element("score_item", array('id'), array(
            'type',
            'course_modules_id',
            'score_min',
            'score_max',
            'timecreated',
            'timemodified',
        ));

        if($userinfo) {
            $score_grades = new backup_nested_element("score_grades");
            $score_grade = new backup_nested_element("score_grade", array('id'), array(
                'score_items_id',
                'user_id',
                'score',
                'usermodified',
                'timecreated',
                'timemodified',
            ));
        }

        // Build the tree
        $plugin->add_child($pluginwrapper);

        $pluginwrapper->add_child($score_items);
        $score_items->add_child($score_item);

        if ($userinfo) {
            $pluginwrapper->add_child($score_grades);
            $score_grades->add_child($score_grade);
        }

        // Define sources
        $score_item->set_source_table('local_adler_scores_items', array('course_modules_id'=>backup::VAR_MODID));

        if ($userinfo) {
            $score_grade->set_source_sql(
                // All grades for current CM
                'SELECT asg.* '.
                'FROM {local_adler_scores_grades} as asg, {local_adler_scores_items} as asi '.
                'WHERE asg.scores_items_id = asi.id AND asi.course_modules_id = ?',
                array(backup_helper::is_sqlparam(backup::VAR_MODID)));
        }

        // Define id annotations
//        $score_item->annotate_ids('score_item', 'scores_types_id');
        if ($userinfo) {
            $score_grade->annotate_ids('user', 'user_id');
        }

        // Define file annotations

        // Return the root element (choice), wrapped into standard activity structure
        return $plugin;
    }
}