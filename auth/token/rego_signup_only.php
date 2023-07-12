<?php
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->libdir . '/formslib.php');
require_once ($CFG->dirroot . '/user/profile/lib.php');

class login_signup_form extends moodleform
{

    function definition() {
        global $CFG, $DB, $tokenqs, $detailsqs;

        // if (isset($detailsqs) && !empty($detailsqs)) {
        //     // details should be encoded to base64 - in php that's
        //     // $val = strtr(base64_encode($val), '+/=', '-_,')
        //     // + replaced with -
        //     // / replaced with _
        //     // = replaced with ,
        //     $detailsqs = base64_decode(strtr($detailsqs, '-_,', '+/='));
        //     parse_str($detailsqs, $params);
        // }

        if (!file_exists($CFG->dirroot . '/enrol/token/lib.php')) {
            echo "Invalid plugin configuration, /enrol/token/lib.php was not found, cannot continue";
            exit;
        };

        $mform = $this->_form;

        // $mform->addElement('static', 'createaccountinstructions', '', get_string('createaccountinstructions', 'auth_token'));

        // $mform->addElement('html', '<div class="clearer"><!-- --></div>');
        $mform->addElement('text', 'username', get_string('signup_email','auth_token'), 'size="30"');
        $mform->setType('username', PARAM_NOTAGS);
        $mform->addRule('username', '', 'required', null, 'server');
        // if (isset($params["username"])) {
        //     $mform->setDefault('username', $params["username"]);
        // }
        //$mform->addRule('username', get_string('missingemail'), 'email', null, 'server');





        // // if (!empty($CFG->passwordpolicy)) $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        // $mform->addElement('html', '<div class="clearer"><!-- --></div>');
        // $mform->addElement('text', 'password', get_string('password'), 'maxlength="32" size="12"');
        // $mform->setType('password', PARAM_RAW);
        // $mform->addRule('password', get_string('missingpassword'), 'required', null, 'server');

        $mform->addElement('html', '<div class="clearer"><!-- --></div>');
        $nameordercheck = new stdClass();
        $nameordercheck->firstname = 'a';
        $nameordercheck->lastname = 'b';
        $nameordercheck->firstnamephonetic = 'b';
        $nameordercheck->lastnamephonetic = 'b';
        $nameordercheck->middlename = 'b';
        $nameordercheck->alternatename = 'b';
        if (fullname($nameordercheck) == 'b a') {
            $mform->addElement('text', 'lastname', get_string('lastname'), 'maxlength="100" size="30"');
            $mform->addElement('html', '<div class="clearer"><!-- --></div>');
            $mform->addElement('text', 'firstname', get_string('firstname'), 'maxlength="100" size="30"');
        }
        else {
            $mform->addElement('text', 'firstname', get_string('firstname'), 'maxlength="100" size="30"');
            $mform->addElement('html', '<div class="clearer"><!-- --></div>');
            $mform->addElement('text', 'lastname', get_string('lastname'), 'maxlength="100" size="30"');
        }

        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', get_string('missingfirstname'), 'required', null, 'server');
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('missinglastname'), 'required', null, 'server');

        // if (isset($params["firstname"])) {
        //     $mform->setDefault('firstname', $params["firstname"]);
        // }
        // if (isset($params["lastname"])) {
        //     $mform->setDefault('lastname', $params["lastname"]);
        // }

        $mform->addElement('html', '<div class="clearer"><!-- --></div>');
        $mform->addElement('static', 'password', get_string('password'), get_string('signup_passwordemailed', 'auth_token'));

        profile_signuwp_fields($mform);

        if (!empty($CFG->sitepolicy)) {
            $mform->addElement('html', '<div class="clearer"><!-- --></div>');
            $mform->addElement('header', 'policyagreement', get_string('policyagreement'), '');
            $mform->setExpanded('policyagreement');
            $mform->addElement('static', 'policylink', '', '<a href="' . $CFG->sitepolicy . '" onclick="this.target=\'_blank\'">' . get_string('policyagreementclick') . '</a>');
            $mform->addElement('checkbox', 'policyagreed', get_string('policyaccept'));
            $mform->addRule('policyagreed', get_string('policyagree'), 'required', null, 'server');
        }

        // buttons
        $this->add_action_buttons(false, get_string('createaccount'));

        // routine to prepopulate fields, if their values have been set
            $scripttag = "<script type=\"text/javascript\">
document.addEventListener('DOMContentLoaded', function() {
    const loc = location.href.split('?'); if (loc[1]) {
        const params = loc[1].split('&'); params.forEach(function(value) {
            const pair = value.split('=');
            const node = document.querySelector('input[name=\"' + pair[0] + '\"]');
            if (node) node.value = pair[1];
        });
    }
});</script>";
            $mform->addElement("html", $scripttag);
    }

    function definition_after_data() {
        $mform = $this->_form;
        $mform->applyFilter('username', 'trim');
    }

    function validation($data, $files) {
        global $CFG, $DB;

        $errors = parent::validation($data, $files);
        $authplugin = get_auth_plugin("token"); // $CFG->registerauth);
	$trimmed = trim($data['username']);
        if (empty($trimmed)) $errors['username'] = get_string('missingemail');
        if (!isset($errors['username'])) {
            if ($DB->record_exists('user', array('username' => $data['username'], 'mnethostid' => $CFG->mnet_localhost_id))) {
                $errors['username'] = get_string('usernameexists');
            }
            if ($authplugin->user_exists($data['username'])) {
                $errors['username'] = get_string('usernameexists');
            }
            if (!validate_email($data['username'])) {
                $errors['username'] = get_string('invalidemail');
            } else if ($DB->record_exists('user', array('email' => $data['username']))) {
                $errors['username'] = get_string('emailexists'); //  . ' <a href="forgot_password.php">' . get_string('newpassword') . '?</a>';
            }
        }
        if (!isset($errors['username'])) if ($err = email_is_not_allowed($data['username'])) $errors['username'] = $err;

        require_once ($CFG->dirroot . '/enrol/token/lib.php');

        $tokenValue = $data['token'];
		if (!empty($tokenValue)) {
    	    $tve = enrol_token_plugin::getTokenValidationErrors($tokenValue);
			if (isset($tve) && $tve !== '') {
        	    $errors['token'] = $tve;
        	}
		}

        return $errors;
    }

    private function addRequiredMembersToData($data) {
        global $CFG;

        $data->email = $data->username;
        $data->email2 = $data->email;
        $data->city = 'None Specified';
        $data->country = $CFG->country;

        return $data;
    }

    function get_data() {
        if (($data = parent::get_data()) === null) return null;

        return $this->addRequiredMembersToData($data);
    }

    function get_submitted_data() {
        if (($data = parent::get_submitted_data()) === null) return null;

        return $this->addRequiredMembersToData($data);
    }
}
?>
