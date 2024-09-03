<?php

namespace local_adler\local\backport;


use cm_info;
use core_completion\cm_completion_details as core_cm_completion_details;

if (get_config('moodle', 'version') < 2023100900) {
    class backport_cm_completion_details extends core_cm_completion_details {
        /**
         * Backport from Moodle 4.4 for Moodle versions below 4.3
         *
         * Whether this activity module instance tracks completion manually.
         *
         * @return bool
         */
        public function is_manual(): bool {
            return $this->cminfo->completion == COMPLETION_TRACKING_MANUAL;
        }

        /**
         * Backport from Moodle 4.4 for Moodle versions below 4.3
         *
         * Returns whether the overall completion state of this course module should be marked as complete or not.
         * This is based on the completion settings of the course module, so when the course module requires a passing grade,
         * it will only be marked as complete when the user has passed the course module. Otherwise, it will be marked as complete
         * even when the user has failed the course module.
         *
         * @return bool True when the module can be marked as completed.
         */
        public function is_overall_complete(): bool {
            $completionstates = [];
            if ($this->is_manual()) {
                $completionstates = [COMPLETION_COMPLETE];
            } else if ($this->is_automatic()) {
                // Successfull completion states depend on the completion settings.
                if (property_exists($this->completiondata, 'customcompletion') && !empty($this->completiondata->customcompletion)) {
                    // If the module has any failed custom completion rule the state could be COMPLETION_COMPLETE_FAIL.
                    $completionstates = [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS];
                } else if (isset($this->completiondata->passgrade)) {
                    // Passing grade is required. Don't mark it as complete when state is COMPLETION_COMPLETE_FAIL.
                    $completionstates = [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS];
                } else {
                    // Any grade is required. Mark it as complete even when state is COMPLETION_COMPLETE_FAIL.
                    $completionstates = [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS, COMPLETION_COMPLETE_FAIL];
                }
            }

            return in_array($this->get_overall_completion(), $completionstates);
        }

        /**
         * From Moodle 4.4 to make backports work, as otherwise the baseclass is returned by this method.
 *
         * Generates an instance of this class.
         *
         * @param cm_info $cminfo The course module info instance.
         * @param int $userid The user ID that we're fetching completion details for.
         * @param bool $returndetails  Whether to return completion details or not.
         * @return core_cm_completion_details
         */
        public static function get_instance(cm_info $cminfo, int $userid, bool $returndetails = true): core_cm_completion_details {
            $course = $cminfo->get_course();
            $completioninfo = new \completion_info($course);
            return new self($completioninfo, $cminfo, $userid, $returndetails);
        }
    }
} else {
    class backport_cm_completion_details extends core_cm_completion_details {
    }
}
