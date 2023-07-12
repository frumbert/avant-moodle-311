<?php
/**
 * Based on the A4_non_embedded certificate type, customised for Avant
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}

// var_dump($_POST,$_GET);exit;

$pdfpassword = get_config('certificate', 'password');
if (empty($pdfpassword)) {
    $pdfpassword = get_string('defaultpassword','mod_certificate');
}

$pdf = new PDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->SetProtection(array('modify', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'),null,$pdfpassword,1);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

$hide_racgp = optional_param('noracgp',0,PARAM_INT) == 1;
$hide_acrrm = optional_param('noacrrm',0,PARAM_INT) == 1;

$x = 20;
$cme = isset($_GET["cpdnumber"]) ? trim($_GET["cpdnumber"]) : "";
$coursename = $course->fullname;
$coursesize = strlen($coursename) > 40 ? 16 : 22;
$coursepos = strlen($coursename) > 40 ? 121 : 123.5;
$fontsans = get_config('certificate', 'fontsans');
$fontserif = get_config('certificate', 'fontserif');

$pdf->SetTextColor(11, 58, 140);
certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, 0, 0, 210, 297);
certificate_print_text($pdf, $x, 87.5, 'L', $fontsans, '', 22, certificate_getCertificateUser());
certificate_print_text($pdf, $x, $coursepos, 'L', $fontsans, 'B', $coursesize, $coursename);
if (!empty($cme)) {
    certificate_print_text($pdf, $x, 100, 'L', $fontsans, '', 16, $cme);
}

// time spent in course (overridable)
$timevalue = certificate_getCertificateTimeSpent($course->id, $USER->id);
$timespent_str = (isset($_GET["override_timespent"])) ? $_GET["override_timespent"] : certificate_secondsToHhmmss($timevalue);

certificate_print_text($pdf, $x, 170, 'L', $fontsans, '', 16, certificate_avant_get_date($certificate, $certrecord, $course));
certificate_print_text($pdf, $x, 178, 'L', $fontsans, '', 8, 'Total time spent: ' . $timespent_str);

// if ($cpd = certificate_avant_get_cpd($course->id, $USER->id)) {
//     $y = 185;
//     foreach ($cpd as $cpdline) {
//         certificate_print_text($pdf, $x, $y, 'L', $fontsans, '', 10, $cpdline);
//         $y += strlen($cpdline) > 105 ? 10 : 5;
//     }
// }

$metadata = certificate_course_customfields($course);
$pdf->SetTextColor(0,0,0);
$racgp = $metadata['racgp']; if ($hide_racgp) $racgp = false;
$acrrm = $metadata['accrm']; if ($hide_acrrm) $acrrm = false;
$w = 72;
$x = 125;
$y = 280; // bottom of page (297) minus padding
if ($racgp) {
    $h = (937 / 1422) * $w; // abs 1422 x 937
    $y = $y - $h;
    $pdf->Image("{$CFG->dataroot}/mod/certificate/pix/seals/RACGP.png", $x, $y, $w, $h);
    certificate_print_text($pdf, $x + 2, $y + 30, 'C', $fontsans, '', 24, sprintf('%s', $metadata['cert_edu']), 24);
    certificate_print_text($pdf, $x + 26, $y + 30, 'C', $fontsans, '', 24, sprintf('%s', $metadata['cert_mo']), 24);
    certificate_print_text($pdf, $x + 48, $y + 30, 'C', $fontsans, '', 24, sprintf('%s', $metadata['cert_rvw']), 24);
    $pdf->SetTextColor(11, 58, 140);
    certificate_print_text($pdf, $x + 34, $y + 4, 'C', $fontsans, '', 12, sprintf('%s', $metadata['cert_racgp_id']), 48);
}
if ($acrrm) {
    $h = (398 / 809) * $w; // abs 809 x 398
    $y = $y - $h - ($racgp?2:0); // negative offset by my height
    $pdf->Image("{$CFG->dataroot}/mod/certificate/pix/seals/ACRRM.png", $x, $y, $w, $h);
    $pdf->SetTextColor(11, 58, 140);
    certificate_print_text($pdf, $x + 22, $y + 18, 'C', $fontsans, '', 16, sprintf('%s', $metadata['cert_acrrm_id']), 48);
}

//code
$pdf->SetTextColor(224, 224, 224);
certificate_print_text($pdf, 0, 292, 'L', $fontserif, '', 10, certificate_get_code($certificate, $certrecord));


// Custom functions for this certificate

// [3.11] change - extra user columns such as icq, msn, url are all removed
// Honorific (Dr, Prof, etc) is now stored in the Department field
function certificate_getCertificateUser() {
global $USER;
    $name = fullname($USER);
    if (isset($USER->department)) $name = $USER->department . " " . $name;
    return $name;
}




function certificate_avant_get_date($certificate, $certrecord, $course, $userid = null) {
    global $DB, $USER;

    // admin can override
    if (isset($_GET["override_date"])) {
        $override_date = $_GET["override_date"];
        if (!empty($override_date)) {
            $nd = new DateTime($override_date);
            return $nd->format("d M Y h:i A");
        }
    }

    if (empty($userid)) {
        $userid = $USER->id;
    }

    // Set certificate date to current time, can be overwritten later
    $date = $certrecord->timecreated;

    $certificate->printdate = intval($certificate->printdate);

    if ($certificate->printdate == 2) {
        // Get the enrolment end date
        $sql = "SELECT MAX(c.timecompleted) as timecompleted
            FROM {course_completions} c
            WHERE c.userid = :userid
            AND c.course = :courseid";
        if ($timecompleted = $DB->get_record_sql($sql, array('userid' => $userid, 'courseid' => $course->id))) {
            if (!empty($timecompleted->timecompleted)) {
                $date = $timecompleted->timecompleted;
            }
        }
    } else if ($certificate->printdate > 2) {
        if ($modinfo = certificate_get_mod_grade($course, $certificate->printdate, $userid)) {
            $date = $modinfo->dategraded;
        }
    }

    if ($certificate->printdate > 0) {
        $certificatedate = userdate($date, "%d %B %Y %R");
    	// formats: http://php.about.com/od/learnphp/ss/php_functions_3.htm
        // $certificatedate = date('j F Y g:i'); // 2013.12.13 Theo requests issue date to be "date & time when the certificate is generated"
        return $certificatedate;
    }

    return '';
}

// from the course metadata fields find all cert_ prefixed fields for this course instance
function certificate_course_customfields($course, $prefix = "cert_") {
    $meta = [];
    $handler = core_course\customfield\course_handler::create();
    if ($customfields = $handler->export_instance_data($course->id)) {
        foreach ($customfields as $data) {
            if (strpos($data->get_shortname(), $prefix)!== false) {
                $meta[$data->get_shortname()] = $data->get_data_controller()->get_value();
            }
            if ($data->get_shortname()==="racgp"||$data->get_shortname()==="accrm") {
                $meta[$data->get_shortname()] = $data->get_value() === "Yes";
            }
        }
    }
    return $meta;
}

function certificate_avant_get_cpd($courseid, $userid = null) {
    global $CFG, $USER;

    $cpd = null;

// testing:
// return Array(
// 'Royal Australian College of General Practitioners – 2 Points in CPD Activity - Activity 181135 ',
// 'Royal Australasian and New Zealand College of Radiologists – Pending ',
// 'Royal Australian and New Zealand College of Obstetricians and Gynaecologists - 1 Point in Self-education ',
// 'Royal Australasian College of Surgeons – 1 Point in Hour – Activity 181257 ',
// 'Australian College of Rural and Remote Medicine – 1 Point in Core point - Activity 7507 ',
// 'Australian and New Zealand College of Anaesthetists - Pending ',
// 'The Royal Australian and New Zealand College of Ophthalmologists – 1 Point in Risk management & clinical governance level 1 ',
// 'Australasian College for Emergency Medicine – 1 Point in ACEM CPD hour',
// );

    if (file_exists($CFG->dirroot.'/local/aurora/lib.php')) {
        require_once($CFG->dirroot.'/local/aurora/lib.php');

        if (empty($userid)) {
            $userid = $USER->id;
        }

        if (function_exists('local_aurora_get_cpd_information')) {
            if (!($cpd = local_aurora_get_cpd_information($userid, $courseid))) {
                $cpd = null;
            }
        }
    }

    return $cpd;
}


