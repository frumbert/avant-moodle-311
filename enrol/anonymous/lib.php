<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Extended Guest Access enrolment plugin.
 *
 * @package    enrol_anonymous
 * @copyright  2022 tim st.clair
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Extended Guest Access plugin implementation.
 * @author Baptiste Desprez
 * @copyright  2022 tim st.clair
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_anonymous_plugin extends enrol_plugin {


    public function try_autoenrol(stdClass $instance) {
        global $USER;

        return false;
    }

    /**
     * Enrol a user using a given enrolment instance.
     *
     * @param stdClass $instance
     * @param int $userid
     * @param null $roleid
     * @param int $timestart
     * @param int $timeend
     * @param null $status
     * @param null $recovergrades
     */
    public function enrol_user(stdClass $instance, $userid, $roleid = null,
        $timestart = 0, $timeend = 0, $status = null, $recovergrades = null) {
        return;
    }

    /**
     * Enrol a user from a given enrolment instance.
     *
     * @param stdClass $instance
     * @param int $userid
     */
    public function unenrol_user(stdClass $instance, $userid) {
        // Nothing to do, we never enrol here!
        return;
    }

    /**
     * Attempt to automatically gain temporary guest access to course,
     * calling code has to make sure the plugin and instance are active.
     *
     * @param stdClass $instance course enrol instance
     * @return bool|int false means no guest access, integer means end of cached time
     */
    public function try_guestaccess(stdClass $instance) {
        global $CFG;

        $config = get_config('enrol_anonymous');

        if ($instance->customint1 == '1') {
            if (remoteip_in_list($config->anonymous_list_ip)) {
                $context = context_course::instance($instance->courseid);
                load_temp_course_role($context, $CFG->guestroleid);
                return ENROL_MAX_TIMESTAMP;
            }
        }

        if ($instance->customint2 == '1') {
            if (isloggedin() && !isguestuser()) {
                $context = context_course::instance($instance->courseid);
                load_temp_course_role($context, $CFG->guestroleid);
                return ENROL_MAX_TIMESTAMP;
            }
        }

        return false;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/anonymous:config', $context);
    }

    /**
     * Returns localised name of enrol instance.
     *
     * @param stdClass $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        global $DB;

        if (empty($instance)) {
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol);
        } else if (empty($instance->name)) {
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol);
        } else {
            return format_string($instance->name, true, array('context' => context_course::instance($instance->courseid)));
        }
    }

    /**
     * Given a courseid this function returns true if the user is able to enrol or configure ip filtering.
     *
     * @param int $courseid
     * @return bool
     */
    public function can_add_instance($courseid) {
        global $DB;
        $coursecontext = context_course::instance($courseid);
        if (!has_capability('moodle/course:enrolconfig', $coursecontext)
            or ! has_capability('enrol/anonymous:config', $coursecontext)) {
            return false;
        }

        if ($DB->record_exists('enrol', array('courseid' => $courseid, 'enrol' => 'anonymous'))) {
            return false;
        }

        return true;
    }

    /**
     * Called after updating/inserting course.
     *
     * @param bool $inserted true if course just inserted
     * @param stdClass $course
     * @param stdClass $data form data
     * @return void
     */
    public function course_updated($inserted, $course, $data) {
        global $DB;

        if ($inserted) {
            if ($this->get_config('defaultenrol')) {
                $this->add_default_instance($course);
            }
        }
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param object $course
     * @return int id of new instance
     */
    public function add_default_instance($course) {
        $fields = array();

        return $this->add_instance($course, $fields);
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;

        if (!$DB->record_exists('enrol', array('courseid' => $data->courseid, 'enrol' => $this->get_name()))) {
            $this->add_instance($course, (array) $data);
        }

        // No need to set mapping, we do not restore users or roles here.
        $step->set_mapping('enrol', $oldid, 0);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/anonymous:config', $context);
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options() {
        $options = array(ENROL_INSTANCE_ENABLED => get_string('yes'), ENROL_INSTANCE_DISABLED => get_string('no'));
        return $options;
    }

    /**
     * Return an array of valid options for the localnet and authenticated_users.
     *
     * @return array
     */
    protected function get_options() {
        $options = array(
            0 => get_string('no'),
            1 => get_string('yes'),
        );
        return $options;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return boolean
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        global $DB;

        // $options = $this->get_status_options();
        // $mform->addElement('select', 'status', get_string('status', 'enrol_anonymous'), $options);
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname" => value) of submitted data
     * @param array $files array of uploaded files "element_name" => tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name" => "error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        $errors = array();
        return $errors;
    }

}

/**
 * Get icon mapping for font-awesome.
 */
function enrol_anonymous_get_fontawesome_icon_map() {
    return [
        'enrol_anonymous:withoutpassword' => 'fa-id-badge',
    ];
}
