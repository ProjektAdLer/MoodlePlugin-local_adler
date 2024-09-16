<?php

namespace local_adler\local\upgrade;

use cm_info;
use dml_exception;
use local_adler\helpers;
use local_adler\local\db\moodle_core_repository;
use local_adler\local\exceptions\not_an_adler_course_exception;
use local_logging\logger;
use moodle_exception;

class upgrade_3_2_0_to_4_0_0_completionlib {
    private int $course_id;
    private logger $logger;
    private moodle_core_repository $moodle_core_repository;
    public function __construct(int $course_id) {
        $this->logger = new logger('local_adler', self::class);
        $this->course_id = $course_id;
        $this->moodle_core_repository = new moodle_core_repository();
    }

    /**
     * @throws not_an_adler_course_exception
     * @throws moodle_exception
     */
    public function execute(): void {
        // Check if the course is an Adler course
        if (!helpers::course_is_adler_course($this->course_id)) {
            throw new not_an_adler_course_exception();
        }

        $this->upgrade_modules();
        $this->resetCourseCache();
    }

    /**
     * @param cm_info $cm_info
     * @return void
     * @throws dml_exception
     */
    private function upgrade_module(cm_info $cm_info): void {
        if ($cm_info->completion == COMPLETION_TRACKING_MANUAL) {
            if ($cm_info->modname == "h5pactivity") {
                $this->upgrade_h5p_module($cm_info);
            } else {
                $this->upgrade_normal_module($cm_info);
            }
        } else {
            $this->logger->warning('Completion for cm ' . $cm_info->id . ' is already set to auto, skipping');
        }
    }

    /**
     * @return void
     * @throws moodle_exception
     */
    private function resetCourseCache(): void {
        purge_caches(['courses' => $this->course_id]);
        get_fast_modinfo($this->course_id, 0, true);
    }

    /**
     * @return void
     * @throws moodle_exception
     */
    public function upgrade_modules(): void {
        global $DB;
        $cm_infos = get_fast_modinfo($this->course_id)->get_cms();

        $transaction = $DB->start_delegated_transaction();
        foreach ($cm_infos as $cm_info) {
            $this->upgrade_module($cm_info);
        }
        $transaction->allow_commit();
    }

    /**
     * @param cm_info $cm_info
     * @return void
     * @throws dml_exception
     */
    public function upgrade_h5p_module(cm_info $cm_info): void {
        $grade_item = $this->moodle_core_repository->get_grade_item('h5pactivity', $cm_info->instance);
        if ($grade_item) {
            $this->logger->info('Setting completion for h5p cm ' . $cm_info->id . ' to "passing grade"');
            $this->set_completion_to_auto($cm_info, false);
            $this->moodle_core_repository->update_grade_item_record($grade_item->id, [
                'gradepass' => $grade_item->grademax
            ]);
        } else {
            $this->logger->error('No grade item found for h5p cm ' . $cm_info->id);
        }
    }

    /**
     * @param cm_info $cm_info
     * @return void
     * @throws dml_exception
     */
    public function upgrade_normal_module(cm_info $cm_info): void {
        $this->logger->info('Setting completion for cm ' . $cm_info->id . ' to view tracking');
        $this->set_completion_to_auto($cm_info, true);
    }

    /**
     * @param cm_info $cm_info
     * @param bool $view if true: complete on view, if false: complete when achieving a passing grade
     * @return void
     * @throws dml_exception
     */
    public function set_completion_to_auto(cm_info $cm_info, bool $view): void {
        $this->moodle_core_repository->update_course_module_record($cm_info->id, [
            'completion' => 2,
            'completionpassgrade' => $view ? 0 : 1,
            'completionview' => $view ? 1 : 0
        ]);
    }
}