<?php
require_once('../../config.php');
$PAGE->set_url('/assist/login/index.php');
$PAGE->set_context(context_system::instance());

define ('CRYPTKEY', 'AvantAssist2023');              // an encryption secret key (can be any fixed string)
define ('TIMEOUT_MINUTES', 10);                      // how long should the link remain valid (in minutes) ?
define ('ALLOWED_COURSES', ['aa_gp' ,'aa_surgeon']); // idnumbers of the courses that allow this type of logon

$field = '';
$feedback = '';
$extraclasses = '';
$match = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $memberid = optional_param('field', '', PARAM_ALPHANUMEXT);
    $email = optional_param('field', '', PARAM_EMAIL);

    if (empty($memberid) && empty($email)) {
        $feedback = 'Please enter the proper value and try again.';    
    }

    // if the user entered an email address then check it
    if ($email) {
        $users = $DB->count_records('user', ['email'=>$email]);
        if ($users > 1) {
            $feedback = 'Sorry this email address is shared by multiple accounts. You must use the member id instead.';
        } else {
            $match = $DB->get_record('user',['email'=>$email]);
        }
        $field = $email; 

    // if the user entered a memberid, find the memberid then get the associated user account
    // a 'memberid' is a preference of a user rather than a customfield in this system.
    } else if ($memberid) {
        if ($maybe_id = $DB->get_record('user_preferences',['name'=>'memberid','value'=>$memberid])) {
            $match = $DB->get_record('user',['id'=>$maybe_id->userid]);    
            $field = $memberid; 
        }
    }

    // start by assuming there was no match, be careful not to tell the user why not
    if (empty($feedback)) {
        $feedback = 'Sorry this account was not found.';
    }

    if ($match) {

        $course = get_user_course($match->id);
        if ($course < 2) { // 1=system, 0=not-found
            $feedback = 'Sorry you do not have access to this resource.';
        } else { // anything else that matched
            send_link_to_user($match);
            $feedback = 'A logon link has been emailed to you.';
            $extraclasses = 'ok';
        }

    }

} else if ($_SERVER['REQUEST_METHOD'] === 'GET') { // open directly or from email

    $param = optional_param('param','', PARAM_RAW);
    if (empty($param)) {
        // nothing sent in, probably opening for the first time
    } else if ($value = decrypt_link($param)) {
        if (validate_link($value)) {
            $data = get_values($value);
            $courseid = get_user_course($data['id']);
            if ($courseid > 1) {
                logon_the_user($data['id'], $courseid);
            } else {
                $feedback = 'Access is denied to the selected resource.';
            }
        } else {
            $feedback = 'The link has expired.';
        }
    } else {
        $feedback = 'This link was not understood.';
    }
}

/**
 * Encode data to Base64URL
 * @param string $data
 * @return boolean|string
 * @source https://base64.guru/developers/php/examples/base64url
 */
function base64url_encode($data) {
  $b64 = base64_encode($data);
  if ($b64 === false) {
    return false;
  }
  $url = strtr($b64, '+/', '-_');
  return rtrim($url, '=');
}

/**
 * Decode data from Base64URL
 * @param string $data
 * @param boolean $strict
 * @return boolean|string
 */
function base64url_decode($data, $strict = false) {
  $b64 = strtr($data, '-_', '+/');
  return base64_decode($b64, $strict);
}


// get the courseid for the user - they should be a member of any of the ALLOWED_COURSES
function get_user_course($user_id) {
global $DB;
    $matches = "";
    foreach (ALLOWED_COURSES as $cohort) {
        $matches .= "c.idnumber = '{$cohort}' OR ";
    }
    $matches = substr($matches, 0, -4);
    $sql = <<<SQL
SELECT c.id from {cohort} h
INNER JOIN {cohort_members} m ON h.id = m.cohortid
INNER JOIN {enrol} e ON h.id = e.customint1
INNER JOIN {course} c ON e.courseid = c.id
WHERE m.userid = ?
AND ($matches)
SQL;
if ($row = $DB->get_record_sql($sql, [$user_id])) {
        return intval($row->id);
    }
    return 0;
}

// perform a moodle user logon and redirect to the course
function logon_the_user($id, $course) {
global $CFG;

    $user = get_complete_user_data('id', $id);
    $user->loggedin = true;
    $user->site = $CFG->wwwroot;
    complete_user_login($user);

    $url = new moodle_url('/course/view.php',['id' => $course]);
    redirect($url);
}

// decode the parameters and return them as an array
function get_values($params) {
    $result = [];
    $params = str_replace('&amp;','&',$params);
    parse_str($params, $result);
    return $result;
}

// check to see if the payload contains the required fields and hasn't expired
function validate_link($payload) {
    $result = get_values($payload);
    if (!isset($result['ts'])) {
        return false;
    }
    if (!isset($result['id'])) {
        return false;
    }
	$theirs = new DateTime("@{$result['ts']}");
	$diff = floatval(date_diff(date_create("now"), $theirs)->format("%i"));
    if ($diff > TIMEOUT_MINUTES) {
        return false;
    }
    return true;
}

// decode the url and decrypt the value inside
function decrypt_link($encoded_value) {
    $data = base64url_decode( $encoded_value );
	list($encrypted_data, $iv) = explode('::', $data, 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', CRYPTKEY, 0, $iv);
}

// send an email containing a logon link to the user
function send_link_to_user($user) {
    $value = http_build_query([
        "id" => $user->id,
        "ts" => time()
    ], '', '&');

	$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
	$encrypted = openssl_encrypt($value, 'aes-256-cbc', CRYPTKEY, 0, $iv);
	$encoded_parameter = base64url_encode( $encrypted . '::' . $iv );

    $url = (new \moodle_url('/assist/login/index.php', ['param'=>$encoded_parameter]))->out();
    $messagetext = <<<OUTPUT
Hi

Click on the link to log on to Avant Assist.

$url

Regards,
Avant Assist team.
OUTPUT;
    $subject = "Avant Assist Logon";
    $emailfrom = core_user::get_noreply_user();

    email_to_user($user, $emailfrom,
                $subject,
                $messagetext);
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avant Assist : Login</title>
    <link href="style.css" type="text/css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/assist/login/favicon.ico">
</head>
<body>
    <main class="container">
    <header>
        <img src="https://www.avant.org.au/images/ART_Avant_Mutual_Logo_RGB.PNG" alt="Avant logo">
    </header>
    <main>
        <h1>Log on to Avant Assist</h1>
        <p>Enter your email address or member id in the box below and press Next.</p>
        <form method="post">
            <p><input type="text" name="field" value="<?php echo $field; ?>" placeholder="Member Id or Email" size="40" /></p>
            <p class='feedback <?php echo $extraclasses; ?>'><?php echo $feedback; ?></p>
            <p><input type="submit" value="Next"></p>
        </form>
    </main>
    <footer>
        <p><a href="https://www.avant.org.au/Terms-of-Use/" title="Terms of use">Terms of use</a> | <a href="https://www.avant.org.au/Privacy-Policy/" title="Privacy policy">Privacy policy</a><br>
        <span>&copy;  Avant Mutual Group Limited <?php echo ~~date('Y'); ?></span></p>
    </footer>
    </main>
</body>
</html>