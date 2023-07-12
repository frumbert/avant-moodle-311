<?php

/**
 * Avant Assist course opener
 *
 * Course actions such as enrolment can take place before the page theme loads
 * We need to log out anonymous users before that takes place
 * Also it's nice to be able to open courses using idnumber
 *
 * @author     Tim St Clair <tim.stclair@gmail.com>
 * @copyright  2022 Avant
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');

$id          = optional_param('id', 0, PARAM_INT);
$name        = optional_param('name', '', PARAM_TEXT);
$idnumber    = optional_param('idnumber', '', PARAM_RAW);
$source      = optional_param('source', 0, PARAM_INT);

$params = array();
if (!empty($name)) {
    $params = array('shortname' => $name);
} else if (!empty($idnumber)) {
    $params = array('idnumber' => $idnumber);
} else if (!empty($id)) {
    $params = array('id' => $id);
}else {
    print_error('unspecifycourseid', 'error');
}

// an error will have occurred if we haven't gotten expected data

$course = $DB->get_record('course', $params, '*', MUST_EXIST);

// by now we either know the course existed or we have thrown an error

// if source was set then we came from the login link on the end of the survey.
    $rec = new stdClass();
    $rec->time = time();
    $rec->userid = $USER->id;
    $rec->ip = $_SERVER['SERVER_ADDR'];
    $rec->course = $course->id;
    $rec->module = 'assist';
    $rec->cmid = 0;
if ($source > 0) {
    $rec->action = 'internal';
} else {
    $rec->action = 'external';
}
    $rec->url = '/assist/open.php?idnumber='. $course->idnumber;
    $rec->info = $source;
    $DB->insert_record('log', $rec);

// don't allow anonymous logons through anything sent to 'open.php'
if (file_exists($CFG->dirroot . '/auth/anonymous/lib.php')) {
    require_once($CFG->dirroot . '/auth/anonymous/lib.php');
    auth_anonymous_autologout();
}

// ok, go to the course now
redirect($CFG->wwwroot .'/course/view.php?id=' . $course->id);
