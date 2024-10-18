<?php

namespace local_adler;


use cm_info;
use context_course;
use dml_exception;
use local_adler\local\db\adler_course_module_repository;
use local_adler\local\exceptions\not_an_adler_cm_exception;
use local_adler\local\exceptions\user_not_enrolled_exception;
use local_logging\logger;
use moodle_exception;
use stdClass;

/**
 * Managing adler score system for one course module
 */
class adler_score {
    private logger $logger;
    private object $course_module;
    private int $user_id;
    protected stdClass $score_item;
    private adler_course_module_repository $adler_course_module_repository;
    protected static string $helpers = helpers::class;
    protected static string $adler_score_helpers = adler_score_helpers::class;

    /**
     * @param cm_info $course_module can be retrieved through get_fast_modinfo($course_id)->get_cm($cm_id), see {@link cm_info}
     * @param int|null $user_id If null, the current user will be used
     * @throws user_not_enrolled_exception
     * @throws moodle_exception course_module_format_not_valid, not_an_adler_cm, course_not_adler_course
     */
    public function __construct(cm_info $course_module, int $user_id = null) {
        global $USER;
        $this->logger = new logger('local_adler', 'adler_score');
        $this->adler_course_module_repository = new adler_course_module_repository();
        $this->course_module = $course_module;
        $this->user_id = $user_id ?? $USER->id;

        // validate user is enrolled in course
        $course_context = context_course::instance($this->course_module->course);
        if (!is_enrolled($course_context, $this->user_id)) {
            throw new user_not_enrolled_exception();
        }

        // validate course is adler course
        if (!static::$helpers::course_is_adler_course($this->course_module->course)) {
            throw new moodle_exception('not_an_adler_course', 'local_adler');
        }

        // get adler score metadata object
        try {
            $this->score_item = $this->adler_course_module_repository->get_adler_course_module_by_cmid($this->course_module->id);
        } catch (dml_exception $e) {
            $this->logger->error('Could not get adler score record for cmid ' . $this->course_module->id . ': ' . $e->getMessage());
            throw new not_an_adler_cm_exception();
        }
    }

    /**
     *  Calculates the achieved score for the course module based on its completion state.
     *
     * @return float
     * @throws moodle_exception If completion is not enabled for the course module.
     */
    public function get_score_by_completion_state(): float {
        $cm_completion_details = cm_completion_details::get_instance(
            get_fast_modinfo($this->course_module->course)->get_cm($this->course_module->id),
            $this->user_id
        );

        // check if completion is enabled for this course_module
        if (!$cm_completion_details->has_completion()) {
            throw new moodle_exception('completion_not_enabled', 'local_adler');
        }

        return $cm_completion_details->is_overall_complete() ? $this->score_item->score_max : 0;
    }
}
