<?php
namespace local_adler\external;

use coding_exception;
use completion_info;
use context_course;
use context_module;
use core\di;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use dml_exception;
use dml_transaction_exception;
use invalid_parameter_exception;
use local_adler\adler_score;
use local_adler\local\db\adler_course_module_repository;
use local_adler\local\db\adler_course_repository;
use local_adler\local\db\moodle_core_repository;
use local_adler\local\exceptions\not_an_adler_cm_exception;
use local_adler\local\exceptions\not_an_adler_course_exception;
use moodle_exception;
use restricted_context_exception;

class trigger_event_cm_viewed extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'module_id' => new external_value(PARAM_INT, 'moodle module id', VALUE_REQUIRED),
            )
        );
    }

    public static function execute_returns(): external_function_parameters {
        return lib::get_adler_score_response_multiple_structure();
    }

    /**
     * @throws restricted_context_exception
     * @throws dml_transaction_exception
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function execute($module_id): array {
        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), array(
            'module_id' => $module_id,
        ));

        // get moodle course object $course
        try {
            $course_module = get_coursemodule_from_id(null, $params['module_id'], 0, false, MUST_EXIST);
        } catch (dml_exception $e) {
            // dml_exception is thrown, but not documented in get_coursemodule_from_id function signature
            throw new invalid_parameter_exception('failed_to_get_course_module');
        }
        $course_module_cm_info = get_fast_modinfo($course_module->course)->get_cm($course_module->id);
        $course_id = $course_module->course;
        $course = di::get(moodle_core_repository::class)->get_course_from_course_id($course_id);

        // validate course is adler course
        if (!di::get(adler_course_repository::class)->course_is_adler_course($course->id)) {
            throw new not_an_adler_course_exception();
        }
        // validate course module is adler course module
        if (!di::get(adler_course_module_repository::class)->record_for_cmid_exists($course_module->id)) {
            throw new not_an_adler_cm_exception();
        }

        // security stuff https://docs.moodle.org/dev/Access_API#Context_fetching
        $context = context_course::instance($course_id);
        self::validate_context($context);


        // trigger event
        self::trigger_module_specific_view_event($course_module, $course);


        // return adler score
        $adler_score = new adler_score($course_module_cm_info);
        return ['data' => lib::convert_adler_score_array_format_to_response_structure(
            array($course_module->id => $adler_score->get_score_by_completion_state()))];
    }

    /**
     * @throws coding_exception
     */
    private static function trigger_module_specific_view_event($course_module, $course): void {
        $module_context = context_module::instance($course_module->id);

        // Determine the specific event class for the module
        $event_class = "\\mod_{$course_module->modname}\\event\\course_module_viewed";
        if (!class_exists($event_class)) {
            throw new coding_exception("Event class $event_class does not exist");
        }

        // Trigger the event
        $event = $event_class::create([
            'context' => $module_context,
            'objectid' => $course_module->id,
        ]);
        $event->trigger();

        // completion
        $completion = new completion_info($course);
        $completion->set_module_viewed($course_module);
    }
}
