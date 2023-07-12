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
 * Contains the definiton of the msgraph message processors (sends messages to users via msgraph)
 *
 * @package   message_msgraph
 * @copyright 2021 Daniel Neis Araujo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/message/output/lib.php');

/**
 * The msgraph message processor
 *
 * @package   message_msgraph
 * @copyright 2021 Daniel Neis Araujo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_output_msgraph extends message_output {

    /**
     * Processes the message (sends by msgraph).
     * @param object $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     */
    public function send_message($eventdata) {
        global $CFG, $DB;

        // Skip any messaging suspended and deleted users.
        if ($eventdata->userto->auth === 'nologin' or $eventdata->userto->suspended or $eventdata->userto->deleted) {
            return true;
        }

        // The user the email is going to.
        $recipient = null;

        // Check if the recipient has a different email address specified in their messaging preferences Vs their user profile.
        $emailmessagingpreference = get_user_preferences('message_processor_msgraph_email', null, $eventdata->userto);
        $emailmessagingpreference = clean_param($emailmessagingpreference, PARAM_EMAIL);

        // If the recipient has set an email address in their preferences use that instead of the one in their profile
        // but only if overriding the notification email address is allowed.
        if (!empty($emailmessagingpreference) && !empty($CFG->messagingallowemailoverride)) {
            // Clone to avoid altering the actual user object.
            $recipient = clone($eventdata->userto);
            $recipient->email = $emailmessagingpreference;
        } else {
            $recipient = $eventdata->userto;
        }

        // Check if we have attachments to send.
        $attachment = '';
        $attachname = '';
        if (!empty($CFG->allowattachments) && !empty($eventdata->attachment)) {
            if (empty($eventdata->attachname)) {
                // Attachment needs a file name.
                debugging('Attachments should have a file name. No attachments have been sent.', DEBUG_DEVELOPER);
            } else if (!($eventdata->attachment instanceof stored_file)) {
                // Attachment should be of a type stored_file.
                debugging('Attachments should be of type stored_file. No attachments have been sent.', DEBUG_DEVELOPER);
            } else {
                // Copy attachment file to a temporary directory and get the file path.
                $attachment = $eventdata->attachment->copy_content_to_temp();

                // Get attachment file name.
                $attachname = clean_filename($eventdata->attachname);
            }
        }

        // Configure mail replies - this is used for incoming mail replies.
        $replyto = '';
        $replytoname = '';
        if (isset($eventdata->replyto)) {
            $replyto = $eventdata->replyto;
            if (isset($eventdata->replytoname)) {
                $replytoname = $eventdata->replytoname;
            }
        }

        // We email messages from private conversations straight away, but for group we add them to a table to be sent later.
        $emailuser = true;
        if (!$eventdata->notification) {
            if ($eventdata->conversationtype == \core_message\api::MESSAGE_CONVERSATION_TYPE_GROUP) {
                $emailuser = false;
            }
        }

        if ($emailuser) {
            $result = $this->email_to_user($recipient, $eventdata->userfrom, $eventdata->subject, $eventdata->fullmessage,
                $eventdata->fullmessagehtml, $attachment, $attachname, true, $replyto, $replytoname);
        } else {
            // TODO: add install.xml.
            $messagetosend = new stdClass();
            $messagetosend->useridfrom = $eventdata->userfrom->id;
            $messagetosend->useridto = $recipient->id;
            $messagetosend->conversationid = $eventdata->convid;
            $messagetosend->messageid = $eventdata->savedmessageid;
            $result = $DB->insert_record('message_msgraph_messages', $messagetosend, false);
        }

        // Remove an attachment file if any.
        if (!empty($attachment) && file_exists($attachment)) {
            unlink($attachment);
        }

        return $result;
    }

    /**
     * Creates necessary fields in the messaging config form.
     *
     * @param array $preferences An array of user preferences
     */
    public function config_form($preferences) {
        global $USER, $OUTPUT, $CFG;
        $string = '';

        $choices = array();
        $choices['0'] = get_string('textformat');
        $choices['1'] = get_string('htmlformat');
        $current = $preferences->mailformat;
        $string .= $OUTPUT->container(html_writer::label(get_string('emailformat'), 'mailformat'));
        $string .= $OUTPUT->container(html_writer::select($choices, 'mailformat', $current, false, array('id' => 'mailformat')));
        $string .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'userid', 'value' => $USER->id));

        if (!empty($CFG->allowusermailcharset)) {
            $choices = array();
            $charsets = get_list_of_charsets();
            if (!empty($CFG->sitemailcharset)) {
                $choices['0'] = get_string('site').' ('.$CFG->sitemailcharset.')';
            } else {
                $choices['0'] = get_string('site').' (UTF-8)';
            }
            $choices = array_merge($choices, $charsets);
            $current = $preferences->mailcharset;
            $string .= $OUTPUT->container(html_writer::label(get_string('emailcharset'), 'mailcharset'));
            $string .= $OUTPUT->container(
                html_writer::select($choices, 'preference_mailcharset', $current, false, array('id' => 'mailcharset'))
            );
        }

        if (!empty($CFG->messagingallowemailoverride)) {
            $inputattributes = array('size' => '30', 'name' => 'msgraph_email', 'value' => $preferences->msgraph_email,
                    'id' => 'msgraph_email');
            $string .= html_writer::label(get_string('email', 'message_msgraph'), 'msgraph_email');
            $string .= $OUTPUT->container(html_writer::empty_tag('input', $inputattributes));

            if (empty($preferences->msgraph_email) && !empty($preferences->userdefaultemail)) {
                $string .= $OUTPUT->container(get_string('ifemailleftempty', 'message_msgraph', $preferences->userdefaultemail));
            }

            if (!empty($preferences->msgraph_email) && !validate_email($preferences->msgraph_email)) {
                $string .= $OUTPUT->container(get_string('invalidemail'), 'error');
            }

            $string .= '<br/>';
        }

        return $string;
    }

    /**
     * Parses the submitted form data and saves it into preferences array.
     *
     * @param stdClass $form preferences form class
     * @param array $preferences preferences array
     */
    public function process_form($form, &$preferences) {
        global $CFG;

        if (isset($form->msgraph_email)) {
            $preferences['message_processor_msgraph_email'] = clean_param($form->msgraph_email, PARAM_EMAIL);
        }
        if (isset($form->preference_mailcharset)) {
            $preferences['mailcharset'] = $form->preference_mailcharset;
            if (!array_key_exists($preferences['mailcharset'], get_list_of_charsets())) {
                $preferences['mailcharset'] = '0';
            }
        }
        if (isset($form->mailformat) && isset($form->userid)) {
            require_once($CFG->dirroot.'/user/lib.php');

            $user = core_user::get_user($form->userid, '*', MUST_EXIST);
            $user->mailformat = clean_param($form->mailformat, PARAM_INT);
            user_update_user($user, false, false);
        }
    }

    /**
     * Returns the default message output settings for this output
     *
     * @return int The default settings
     */
    public function get_default_messaging_settings() {
        return MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF;
    }

    /**
     * Loads the config data from database to put on the form during initial form display
     *
     * @param array $preferences preferences array
     * @param int $userid the user id
     */
    public function load_data(&$preferences, $userid) {
        $preferences->msgraph_email = get_user_preferences( 'message_processor_msgraph_email', '', $userid);
    }

    /**
     * Returns true as message can be sent to internal support user.
     *
     * @return bool
     */
    public function can_send_to_any_users() {
        return true;
    }

    /**
     * Send an email to a specified user
     *
     * @param stdClass $user  A {@link $USER} object
     * @param stdClass $from A {@link $USER} object
     * @param string $subject plain text subject line of the email
     * @param string $messagetext plain text version of the message
     * @param string $messagehtml complete html version of the message (optional)
     * @param string $attachment a file on the filesystem, either relative to $CFG->dataroot or a full path to a file in one of
     *          the following directories: $CFG->cachedir, $CFG->dataroot, $CFG->dirroot, $CFG->localcachedir, $CFG->tempdir
     * @param string $attachname the name of the file (extension indicates MIME)
     * @param bool $usetrueaddress determines whether $from email address should
     *          be sent out. Will be overruled by user profile setting for maildisplay
     * @param string $replyto Email address to reply to
     * @param string $replytoname Name of reply to recipient
     * @param int $wordwrapwidth custom word wrap width, default 79
     * @return bool Returns true if mail was sent OK and false if there was an error.
     */
    private function email_to_user($user, $from, $subject, $messagetext, $messagehtml = '', $attachment = '', $attachname = '',
                                   $usetrueaddress = true, $replyto = '', $replytoname = '', $wordwrapwidth = 79) {

        if (empty($user) or empty($user->id)) {
            debugging('Can not send email to null user', DEBUG_DEVELOPER);
            return false;
        }

        if (empty($user->email)) {
            debugging('Can not send email to user without email: '.$user->id, DEBUG_DEVELOPER);
            return false;
        }

        if (!empty($user->deleted)) {
            debugging('Can not send email to deleted user: '.$user->id, DEBUG_DEVELOPER);
            return false;
        }

        if (!empty($CFG->noemailever)) {
            // Hidden setting for development sites, set in config.php if needed.
            debugging('Not sending email due to $CFG->noemailever config setting', DEBUG_NORMAL);
            return true;
        }

        if (email_should_be_diverted($user->email)) {
            $subject = "[DIVERTED {$user->email}] $subject";
            $user = clone($user);
            $user->email = $CFG->divertallemailsto;
        }

        // Skip mail to suspended users.
        if ((isset($user->auth) && $user->auth == 'nologin') or (isset($user->suspended) && $user->suspended)) {
            return true;
        }

        if (!validate_email($user->email)) {
            // We can not send emails to invalid addresses - it might create security issue or confuse the mailer.
            debugging("email_to_user: User $user->id (".fullname($user).") email ($user->email) is invalid! Not sending.");
            return false;
        }

        if (over_bounce_threshold($user)) {
            debugging("email_to_user: User $user->id (".fullname($user).") is over bounce threshold! Not sending.");
            return false;
        }

        // TLD .invalid  is specifically reserved for invalid domain names.
        // For More information, see {@link http://tools.ietf.org/html/rfc2606#section-2}.
        if (substr($user->email, -8) == '.invalid') {
            debugging("email_to_user: User $user->id (".fullname($user).") email domain ($user->email) is invalid! Not sending.");
            return true; // This is not an error.
        }

        if (($principalname = get_config('message_msgraph', 'userprincipalname')) &&
            ($clientid = get_config('message_msgraph', 'clientid')) &&
            ($clientsecret = get_config('message_msgraph', 'clientsecret')) &&
            ($tenant = get_config('message_msgraph', 'tenant'))) {

            $url = 'https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/token';

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
            ]);
            $query = 'grant_type=client_credentials'.
                     '&client_secret='.$clientsecret.
                     '&client_id='.$clientid.
                     '&scope='.urlencode('https://graph.microsoft.com/.default');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

            if (!$result = curl_exec($ch)) {
                var_dump(curl_error($ch));
                return;
            }
            curl_close($ch);

            $decodedresponse = json_decode($result);
            $curl = new \curl();
            $curl->setHeader('Authorization: Bearer '. $decodedresponse->access_token);
            $curl->setHeader('Content-type: application/json');

            $mailurl = "https://graph.microsoft.com/v1.0/users/{$principalname}/sendMail";
            $params = (object)[
                'message' => (object) [
                    'subject' => $subject,
                    'body' => (object) [
                      'contentType' => "Text", // TODO: html format.
                      'content' => $messagetext
                    ],

                    'toRecipients' => [(object)[
                        'emailAddress' => (object)[
                          'address' => $user->email
                        ]
                    ]],
                ],
            ];
            // TODO: attachments.
            if (false) {
                $params->message->attachments = [
                    (object)[
                        '@odata.type' => '#microsoft.graph.fileAttachment',
                        'name' => 'attachment.txt',
                        'contentType' => 'text/plain',
                        'contentBytes' => 'SGVsbG8gV29ybGQh' // It's a base64 encoded content.
                    ]
                ];
            }
            $jsonparams = json_encode($params, JSON_UNESCAPED_UNICODE);
            $result = $curl->post($mailurl, $jsonparams);
            return empty($result);
        }
    }
}
