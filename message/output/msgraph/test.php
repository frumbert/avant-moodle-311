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
 * Teste the email send with Microsoft Graph REST API
 *
 * @package   message_msgraph
 * @copyright 2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('./../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . "/message/output/msgraph/message_output_msgraph.php");

$url = new moodle_url('/message/output/msgraph/test.php');

$title = get_string('pluginname', 'message_msgraph');
$test = get_string('testlink', 'message_msgraph');
$PAGE->set_context(\context_system::instance());
$PAGE->set_title($test . ' - ' . $title);
$PAGE->set_heading($title);
$PAGE->set_url($url);

require_login();
require_capability('moodle/site:config', \context_system::instance());

echo $OUTPUT->header();
echo $OUTPUT->heading($test);

$msgraph = new \message_output_msgraph();

$eventdata = (object)[
  'notification' => false,
  'conversationtype' => \core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
  'userto' => $DB->get_record('user', ['username' => 'admin']),
  'userfrom' => $DB->get_record('user', ['username' => 'guest']),
  'subject' => 'This is a test subject',
  'fullmessage' => 'This is a test body for a test email',
  'fullmessagehtml' => 'This is a test body for a test email',
];
echo '<p>Email output:' . var_export($msgraph->send_message($eventdata)) . '</p>';

echo $OUTPUT->footer();
