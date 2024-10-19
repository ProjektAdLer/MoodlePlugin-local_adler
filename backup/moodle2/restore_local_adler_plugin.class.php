<?php

use core\di;
use local_adler\local\db\adler_course_module_repository;
use local_adler\local\db\adler_course_repository;
use local_adler\local\db\adler_sections_repository;
use local_adler\local\exceptions\not_an_adler_course_exception;
use local_adler\local\upgrade\upgrade_3_2_0_to_4_0_0_completionlib;
use local_logging\logger;

/**
 * Restoring logic for the local Adler plugin.
 */
class restore_local_adler_plugin extends restore_local_plugin {
    // all possible methods: define_course_plugin_structure, define_section_plugin_structure,
    // define_module_plugin_structure, define_grade_item_plugin_structure, define_question_plugin_structure
    private $plugin_release_set_version;
    private $adler_course_repository;
    protected $adler_sections_repository;
    private $adler_course_module_repository;
    private $log;

    public function __construct($name, $plugin, $restore) {
        parent::__construct($name, $plugin, $restore);
        $this->log = new logger('local_adler', self::class);
        $this->adler_course_repository = di::get(adler_course_repository::class);
        $this->adler_sections_repository = di::get(adler_sections_repository::class);
        $this->adler_course_module_repository = di::get(adler_course_module_repository::class);
        $this->plugin_release_set_version = null;
    }

    protected function define_course_plugin_structure(): array {
        return [
            new restore_path_element('plugin_release_set_version', $this->get_pathfor('/')),
            new restore_path_element('adler_course', $this->get_pathfor('/adler_course'))
        ];
    }

    public function process_plugin_release_set_version($data) {
        $data = (object)$data;

        if (property_exists($data, 'plugin_release_set_version') ) {
            if (version_compare($data->plugin_release_set_version, '4.0.0', '<')) {
                throw new moodle_exception('invalid_plugin_release_set_version', 'local_adler', '', null, 'plugin_release_set_version is below 3.2.0');
            }
            $this->plugin_release_set_version = $data->plugin_release_set_version;
        }
    }

    /**
     * @throws dml_exception
     */
    public function process_adler_course($data) {
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
        $this->adler_course_repository->create_adler_course($data);
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
        $this->adler_sections_repository->create_adler_section($data);
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
    public function process_adler_module($data) {
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
        $this->adler_course_module_repository->create_adler_cm($data);
    }

    /**
     * @throws moodle_exception
     * @throws not_an_adler_course_exception
     */
    public function after_restore_course() {
        $this->log->info('Restoring course with plugin set version ' . $this->plugin_release_set_version);
        if (empty($this->plugin_release_set_version)) {
            try {
                (new upgrade_3_2_0_to_4_0_0_completionlib($this->task->get_courseid()))->execute();
            } catch (not_an_adler_course_exception $e) {
                $this->log->info('Course is not an Adler course, skipping adler completionlib change upgrade. This should not happen as this logic should not be called at all if there is no adler course information in the backup.');
                return;
            }
        }
    }
}

