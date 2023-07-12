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

$pdf = new PDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->SetProtection(array('modify'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();


// Define variables
// Landscape
if ($certificate->orientation == 'L') {
    $x = 10;
    $y = 30;
    $sealx = 230;
    $sealy = 150;
    $sigx = 0;
    $sigy = 175;
    $custx = 47;
    $custy = 155;
    $wmarkx = 40;
    $wmarky = 31;
    $wmarkw = 212;
    $wmarkh = 148;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 297;
    $brdrh = 210;
    $codey = 175;
} else { // Portrait
    $x = 10;
    $y = 20;
    $sealx = 126;
    $sealy = 238; // 260, 232
    $sigx = 10;
    $sigy = 232; // 244
    $custx = 30;
    $custy = 230;
    $wmarkx = 26;
    $wmarky = 58;
    $wmarkw = 158;
    $wmarkh = 170;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 210;
    $brdrh = 297;
    $codey = 250;
}

$cn = $course->fullname;


// Add images and lines
certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
certificate_draw_frame($pdf, $certificate);
// Set alpha to semi-transparency
$pdf->SetAlpha(0.5);
certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
$pdf->SetAlpha(1);
certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, 75, '');
certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

// Add text
$pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', '', 30, 'Avant Mutual Group Limited');
$pdf->SetTextColor(102, 102, 102);
certificate_print_text($pdf, $x, $y + 20, 'C', 'Helvetica', '', 18, 'hereby confirms that');
$pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $x, $y + 30, 'C', 'Helvetica', '', 25, fullname($USER));

if (isset($_GET["cpdnumber"])) {
    $cme = $_GET["cpdnumber"];    // CPD / CME number
    if (!(empty($cme))) {
    	certificate_print_text($pdf, $x, $y + 42, 'C', 'Helvetica', '', 18, $cme);
    	$y += 12;	// push subsequent lines down
    }
} else {
    certificate_print_text($pdf, $x, $y + 42, 'C', 'Helvetica', '', 18, var_export($_GET,true));
    $y += 12;
}

$pdf->SetTextColor(102, 102, 102);
certificate_print_text($pdf, $x, $y + 42, 'C', 'Helvetica', '', 18, 'completed The Avant Learning Centre course');
$pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $x, $y + 52, 'C', 'Helvetica', '', 25, $cn);

if (strlen($cn) > 90) $y += 18; // could do some modulo thing, shouldn't come up too much
if (strlen($cn) > 45) $y += 12;

$pdf->SetTextColor(102, 102, 102);
certificate_print_text($pdf, $x, $y + 64, 'C', 'Helvetica', '', 18, 'as part of Avant\'s CPD education program');
certificate_print_text($pdf, $x, $y + 78, 'C', 'Helvetica', '', 14, 'Course was completed on');

$pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $x, $y + 84, 'C', 'Helvetica', '', 25, certificate_avant_get_date($certificate, $certrecord, $course));

if ($cpd = certificate_avant_get_cpd($course->id, $USER->id)) {
    $dy = 100;
    foreach ($cpd as $cpdline) {
        certificate_print_text($pdf, $x, $y + $dy, 'C', 'Helvetica', '', 10, $cpdline);
        $dy += 10;
    }
}

certificate_print_text($pdf, 10, 260, 'L', 'Helvetica', '', 14, 'Ruth Crampton');
certificate_print_text($pdf, 10, 266, 'L', 'Helvetica', '', 14, 'Manager Member Education, Avant');

$pdf->Line(10, 275, 200, 275);

//certificate_print_text($pdf, $x, $codey, 'C', 'Times', '', 10, certificate_get_code($certificate, $certrecord));
//certificate_print_text($pdf, $custx, $custy, 'L', null, null, null, $certificate->customtext);


// Custom functions for this certificate

function certificate_avant_get_date($certificate, $certrecord, $course, $userid = null) {
    global $DB, $USER;

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

function certificate_avant_get_cpd($courseid, $userid = null) {
    global $CFG, $USER;

    $cpd = null;

	// debug
	// return Array('Lorem ipsum dolor sit amet, consectetur adipiscing elit.','Pellentesque vitae pharetra magna, at dictum augue.');

    if (file_exists($CFG->dirroot.'/local/aurora/lib.php')) {
        require_once($CFG->dirroot.'/local/aurora/lib.php');

        if (empty($userid)) {
            $userid = $USER->id;
        }

        if (function_exists('local_aurora_get_cpd_information')) {
            if (!($cpd = local_aurora_get_cpd_information($userid, $courseid))) {
				// add_to_log(0, 'certificate', 'cert get cpd', 'local_aurora', 'call local_aurora_get_cpd_information returned '.print_r($cpd,true));
                $cpd = null;
            }
        }
    }

    return $cpd;
}
