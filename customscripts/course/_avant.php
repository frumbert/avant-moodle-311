<?php 

/**
 * Avant logon sends in
 * @param redirectto string - a parameter that specifies the course 'home page' on the Learning Centre
 * @param auth base64 - an encoded object containing user details (enough to create a user record if need be) - which is consumed by the /auth/aurora plugin
 * @param token boolean - if set to 1, redirect unauthenticated users to the token logon page
 */

$auth        = optional_param('auth', '', PARAM_ALPHANUM);  // base64; this is an aurora logon, do a normal login - btw PARAM_BASE64 doesn't work
$redirectto  = optional_param('redirectto', '', PARAM_RAW); // if set append to wantsurl so breadcrumb modification happens + start at first activity
$token       = optional_param('token', -1, PARAM_BOOL);     // 1; this is a token logon, goto the token page
$urlextra    = ""; // for WANTSURL
$authParams  = ""; // for AUTH link

// try to capture and prevent &errorcode=4
unset($SESSION->has_timed_out);
if (!isset($_SESSION['SESSION'])) {
    $_SESSION['SESSION'] = new \stdClass();
}


// Store course home in a SESSION object, used by theme renderer.
if (!empty($redirectto)) {
    $SESSION->coursehomeurl = $redirectto;
    $urlextra = "&redirectto=$redirectto";
}

// check of the course has guest access enabled
$instances = $DB->get_records('enrol', array('courseid'=>$course->id, 'status'=>ENROL_INSTANCE_ENABLED), 'sortorder, id ASC');
$enrols = enrol_get_plugins(true);
$has_guest_enrolment = false;
foreach ($instances as $instance) {
    if (!isset($enrols[$instance->enrol])) {
        continue;
    }
    if ($instance->enrol === "guest") $has_guest_enrolment = true;
}

// if the user is a guest but the course doesn't allow guest access, log them out
if (isguestuser() && !$has_guest_enrolment) {
    $USER->id = 0;
}

// if the user is not yet logged on, the course may allow guest access
if (!isloggedin() && $has_guest_enrolment) {
    $guest = get_complete_user_data('id', $CFG->siteguest);
    complete_user_login($guest);
}

// work out the correct logon page for unauthenticated users based on supplied parameters
if ((!isloggedin() || isguestuser()) && !empty($auth)) {
    $SESSION->wantsurl = $CFG->wwwroot.'/course/view.php?id='.$id . $urlextra;
    if (1 == $token) {
        redirect($CFG->wwwroot.'/auth/token/login.php?auth=' . $auth . $authParams);
    } else {
        redirect($CFG->wwwroot.'/login/index.php?auth=' . $auth . $authParams); // 2021.08.19 added auth param instead of plain; matching code in /auth/aurora
    }
    die(1);
} else if (((!isloggedin() || isguestuser()) && !empty($redirectto) && 1 == $token)) {
    redirect($CFG->wwwroot.'/auth/token/login.php');
}