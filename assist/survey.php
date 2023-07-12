<?php
/**
 * This file is NOT part of Moodle - http://moodle.org/
 *
 * A simple page to generate an anonymous login token.
 * Detects crawlers/bots and stops them from generating tokens.
 * @copyright  2022 Tim St Clair
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once('./vendor/autoload.php');

use Jaybizzle\CrawlerDetect\CrawlerDetect;

$ts = microtime();
$id = required_param('id', PARAM_INT); // throws to Moodle error when missing
$key = optional_param('key', $ts, PARAM_RAW); // value to identify user if required

$COURSE_TO_OPEN = 0;
$COHORT_TO_ENROL = "anonymous";
$COHORT_VERIFY = "";

$GP                 = crc32("gp");          // 996187811
$SURGEON            = crc32("surgeon");     // 949208915

$GP_EVAL            = crc32("gpeval");      // 965155528
$SURGEON_EVAL       = crc32("surgeoneval");  // 2048195220

$ANON               = crc32("anonymous");   // 3258052014
$requires_email_key = false;

$CrawlerDetect = new CrawlerDetect;

if ($CrawlerDetect->isCrawler()) {
    http_response_code(403);
    die('Forbidden');

} else {

    switch ($id) {

        case $SURGEON:
            $course = $DB->get_record('course', array('idnumber' => 'aa_surgeon_anon'), '*', MUST_EXIST);
            $COURSE_TO_OPEN = $course->id;
            $COHORT_TO_ENROL = "aa_surgeon_anon";
            break;

        case $GP:
            $course = $DB->get_record('course', array('idnumber' => 'aa_gp_anon'), '*', MUST_EXIST);
            $COURSE_TO_OPEN = $course->id;
            $COHORT_TO_ENROL = "aa_gp_anon";
            break;

        case $SURGEON_EVAL:
            $course = $DB->get_record('course', array('idnumber' => 'aa_surgeon_eval'), '*', MUST_EXIST);
            $COURSE_TO_OPEN = $course->id;
            $COHORT_VERIFY = "aa_surgeon";
            $COHORT_TO_ENROL = "aa_surgeon_eval";
            $requires_key = true;
            break;

        case $GP_EVAL:
            $course = $DB->get_record('course', array('idnumber' => 'aa_gp_eval'), '*', MUST_EXIST);
            $COURSE_TO_OPEN = $course->id;
            $COHORT_VERIFY = "aa_gp";
            $COHORT_TO_ENROL = "aa_gp_eval";
            $requires_key = true;
            break;

        case $ANON:
            $COURSE_TO_OPEN = 154; // 153;
            $COHORT_TO_ENROL = "test_rate_cohort"; // "anonymous";
            break;

        default:
            http_response_code(403);
            die('Bad Identifier ' . $id . ', '. $key);

    }

}

if (isset($CFG->aa_fullaccess) && $CFG->aa_fullaccess == true && $requires_key) {
    $requires_key = false;
}

if ($requires_key) {

    // asked for key but got none / default
    if (empty($key) || ($key === $ts)) {
        die('Bad key');
    }

    // if the key was an email address, verify the details
    if (strpos($key,'@') !== false) {
        $user = $DB->get_record('user', ['email'=>$key]);
        if (!$user) die('Bad url: Key not found');
        $cohort = $DB->get_record('cohort', array('idnumber' => $COHORT_VERIFY));
        if (!$cohort) die('Bad url: Cohort not found');
        if (!$DB->record_exists('cohort_members', array('cohortid'=>$cohort->id, 'userid'=>$user->id))) {
            die('Permission denied: User not enrolled');
        }
    }

    // if the key was a memberid, verify the details
    if ($match = $DB->get_record('user_preferences', ['name'=>'memberid','value'=>$key])) {
        $cohort = $DB->get_record('cohort', array('idnumber' => $COHORT_VERIFY));
        if (!$cohort) die('Bad url: Cohort not found');
        if (!$DB->record_exists('cohort_members', array('cohortid'=>$cohort->id, 'userid'=>$match->userid))) {
            die('Permission denied: User not enrolled');
        }
    }

}

$qs = http_build_query([
    "key" => $key, // some value to make the token unique; becomes the account username
    "anon" => 1,
    "course" => $COURSE_TO_OPEN,
    "cohort" => $COHORT_TO_ENROL,
    "ts" => time(),
    "force" => 1
], '', '&'); // APPARENTLY on the INTELLEX server &amp; is encoded instead of & even though ini_get('arg_separator.output') returns nothing. Hmm.

// var_dump($qs); die();

$url = http_build_query($qs, '', '&'); // APPARENTLY on the INTELLEX server &amp; is encoded instead of & even though ini_get('arg_separator.output') returns nothing. Hmm.

// echo $url, '<br />';
// echo '<a href="/login/index.php?auth=',base64_encode($url),'">/login/index.php?auth=' , base64_encode($url) , '</a>';
// die();

@header("X-Redirect-By: AvantAssist Survey");
@header($_SERVER['SERVER_PROTOCOL'] . ' 303 See Other');
header('Location: /login/index.php?auth=' . base64_encode($url));