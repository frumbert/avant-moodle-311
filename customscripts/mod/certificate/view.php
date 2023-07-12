<?php

// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * Handles viewing a certificate
 *
 * @package    mod_certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// echo realpath(dirname(__FILE__) . '/../../../');
// exit;

// $tt = time();

require_once(dirname(__FILE__) . '/../../../config.php');
require_once("$CFG->dirroot/mod/certificate/locallib.php");
require_once("$CFG->dirroot/mod/certificate/deprecatedlib.php");
require_once("$CFG->libdir/pdflib.php");

$id = required_param('id', PARAM_INT);    // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA);
$edit = optional_param('edit', -1, PARAM_BOOL);

if (!$cm = get_coursemodule_from_id('certificate', $id)) {
    print_error('Course Module ID was incorrect');
}
if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
    print_error('course is misconfigured');
}
if (!$certificate = $DB->get_record('certificate', array('id'=> $cm->instance))) {
    print_error('course module is incorrect');
}

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/certificate:view', $context);

$event = \mod_certificate\event\course_module_viewed::create(array(
    'objectid' => $certificate->id,
    'context' => $context,
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('certificate', $certificate);
$event->trigger();

$completion=new completion_info($course);
$completion->set_module_viewed($cm);

// page will require jquery
$PAGE->requires->jquery();

// Initialize $PAGE, compute blocks
$PAGE->set_url('/mod/certificate/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($certificate->name));
$PAGE->set_heading(format_string($course->fullname));

if (($edit != -1) and $PAGE->user_allowed_editing()) {
     $USER->editing = $edit;
}

$fudgeScript = <<<FUDGE
function fudgeCertificateDate() {
    var dest = jQuery("#cpdcme");
    if (!dest) return;
	var e = jQuery.Event( 'keydown', { keyCode: 32, which: 32 } );
	dest.trigger(e).focus();
}
FUDGE;

$cpdScript = <<<HACK
var _cpdHacked = false;
jQuery(function() {
    var _hack = jQuery("#cpdcme");

    if (!_hack.length) return;

    _hack.on("keypress focus", function () {
		if (_cpdHacked != true) {
            var _t = jQuery(this);
            jQuery('.avant-certificate-content .singlebutton').remove();
			var _newdiv = jQuery("<div style='text-align:center'><div class='singlebutton'></div></div>");
			var _btn = jQuery("<button />").addClass('btn btn-primary').text("Get your certificate").wrap("");
			_btn.on("click", function () {
				var url = location.href + "&action=get&cpdnumber=" + escape(_t.val());
				if ($("#completion_date_override").length) {
					var _val = $("#completion_date_override").val();
					url += "&override_date=" + escape(_val);
				}
				if ($("#completion_time_override").length) {
					var _val = $("#completion_time_override").val();
					url += "&override_timespent=" + escape(_val);
				}

				if (!$("#racgp_override").is(':checked')) {
					url += "&noracgp=1";
				}
				if (!$("#acrrm_override").is(':checked')) {
					url += "&noacrrm=1";
				}

                // document.getElementById('certificate_feedback').textContent = url;

				var w = window.open(url, "certificate", "height=1024,width=768,top=0,left=0,menubar=0,location=0,scrollbars,resizable,toolbar,status,directories=0,fullscreen=0,dependent");
				w.focus();
			});
			_newdiv.find(".singlebutton").append(_btn);
			_newdiv.insertAfter(_hack);
			_cpdHacked = true;
		}
	});
});
HACK;

// Add block editing button
if ($PAGE->user_allowed_editing()) {
    $editvalue = $PAGE->user_is_editing() ? 'off' : 'on';
    $strsubmit = $PAGE->user_is_editing() ? get_string('blockseditoff') : get_string('blocksediton');
    $url = new moodle_url($CFG->wwwroot . '/mod/certificate/view.php', array('id' => $cm->id, 'edit' => $editvalue));
    $PAGE->set_button($OUTPUT->single_button($url, $strsubmit));
}

// Check if the user can view the certificate
if ($certificate->requiredtime && !has_capability('mod/certificate:manage', $context)) {
    if (certificate_get_course_time($course->id) < ($certificate->requiredtime * 60)) {
        $a = new stdClass;
        $a->requiredtime = $certificate->requiredtime;
        notice(get_string('requiredtimenotmet', 'certificate', $a), "$CFG->wwwroot/course/view.php?id=$course->id");
        die;
    }
}

// $nn = time() - $tt;
// echo "setup took $nn ms<br>";
// $tt = time();

// Create new certificate record, or return existing record
$certrecord = certificate_get_issue($course, $USER, $certificate, $cm);

make_cache_directory('tcpdf');
// Load the specific certificate type.
require("$CFG->dirroot/mod/certificate/type/$certificate->certificatetype/certificate.php");

// $nn = time() - $tt;
// echo "certificate require took $nn ms<br>";

if (empty($action)) { // Not displaying PDF
    echo $OUTPUT->header();

    echo "<div class='avant-certificate-content'>";

    $viewurl = new moodle_url('/mod/certificate/view.php', array('id' => $cm->id));
    groups_print_activity_menu($cm, $viewurl);
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

    if (has_capability('mod/certificate:manage', $context)) {
// $tt = time();
        $numusers = count(certificate_get_issues($certificate->id, 'ci.timecreated ASC', $groupmode, $cm));
// $nn = time() - $tt;
        $url = html_writer::tag('a', get_string('viewcertificateviews', 'certificate', $numusers),
            array('href' => $CFG->wwwroot . '/mod/certificate/report.php?id=' . $cm->id));
        echo html_writer::tag('div', $url, array('class' => 'reportlink'));
// echo "count took $nn ms<br>";
    }

    if (!empty($certificate->intro)) {
        echo $OUTPUT->box(format_module_intro('certificate', $certificate, $cm->id), 'generalbox', 'intro');
    }

    if ($attempts = certificate_get_attempts($certificate->id)) {

            // MOD: Render the table here rather than calling certificate_print_attempts() since it messes with the output buffer
            $table = new html_table();
            $table->class = 'generaltable';
            $table->head = array('Your certificates for this course');
            $table->align = array('left');
            $table->attributes = array("style" => "width:50%; margin:0 auto 1rem");
            $gradecolumn = $certificate->printgrade;
            if ($gradecolumn) {
                $table->head[] = get_string('grade');
                $table->align[] = 'center';
                $table->size[] = '';
            }
            // One row for each attempt
            foreach ($attempts as $attempt) {
                $row = array();

                // prepare strings for time taken and date completed
                $datecompleted = userdate($attempt->timecreated);
                $row[] = $datecompleted;

                if ($gradecolumn) {
                    $attemptgrade = certificate_get_grade($certificate, $course);
                    $row[] = $attemptgrade;
                }

                $table->data[$attempt->id] = $row;
            }

            echo html_writer::table($table);

    }

    if ($certificate->delivery == 0)    {
        $str = get_string('openwindow', 'certificate');
    } elseif ($certificate->delivery == 1)    {
        $str = get_string('opendownload', 'certificate');
    } elseif ($certificate->delivery == 2)    {
        $str = get_string('openemail', 'certificate');
    }
    echo html_writer::tag('p', $str, array('style' => 'text-align:center'));
    $linkname = get_string('getcertificate', 'certificate');


/* MOD */
    if (certificate_is_roleswitched()) {
        $d = certificate_avant_get_date($certificate, $certrecord, $course);
        $now = date('Y-m-d\TH:i',strtotime($d));
// $tt = time();
        $totaltime = certificate_getCertificateTimeSpent($course->id, $USER->id);
// $nn = time() - $tt;
// echo "calculation took $nn ms<br>";
        // $totaltime = certificate_getScormTotalTime($course->id);
        $time = certificate_secondsToHhmmss($totaltime);
        echo html_writer::start_tag("fieldset", array("style" => "border: 1px solid rgb(164,0,0); padding: 10px; margin: 0 auto 1rem; width: 50%"));
        echo html_writer::tag("legend","Administrative Overrides",["style"=>"color:rgb(164,0,0)"]);
        echo html_writer::tag("p","As an admin, you're able to override values shown on this users certificate. The Press the Apply button before using the 'Get your certificate' button.");
        echo html_writer::tag("p","<label>Completion date: <input type='datetime-local' id='completion_date_override' value='{$now}'></label> <button onclick='fudgeCertificateDate()'>Apply</button>", array("style" => "text-align: center"));
        // echo html_writer::tag("p","<label>Time spent: <input type='time' id='completion_time_override' value='{$time}' step='1' title='12am is the same as 0 hours'></label> <button onclick='fudgeCertificateDate()'>Apply</button>", array("style" => "text-align: center"));
        echo html_writer::tag("p","<label>Time spent: <input type='text' size='9' id='completion_time_override' value='{$time}'></label> <button onclick='fudgeCertificateDate()'>Apply</button>", array("style" => "text-align: center"));
        echo html_writer::tag("p","<label><input type='checkbox' id='racgp_override' value='1' checked onchange='fudgeCertificateDate()' title='Tick means visible'> Show RACGP fields?</label>", array("style" => "text-align: center"));
        echo html_writer::tag("p","<label><input type='checkbox' id='acrrm_override' value='1' checked onchange='fudgeCertificateDate()' title='Tick means visible'> Show ACRRM fields?</label>", array("style" => "text-align: center"));
        echo html_writer::end_tag("fieldset");
        echo html_writer::tag("script",$fudgeScript);
    }
    echo html_writer::start_tag('p', ['style' => 'text-align:center']);
    echo html_writer::tag('label', 'To show a CPD/CME number on the certificate, enter it in the box below', ['for' => 'cpdcme']);
    echo html_writer::empty_tag('br');
    echo html_writer::empty_tag('input', ['size'=>20, 'id'=>'cpdcme', 'class' => 'form-control', 'style' => 'width: 256px; margin: 0 auto 1rem;', 'placeholder' => 'Enter your CPD/CME number']);
    echo html_writer::end_tag('p');
    echo html_writer::tag('script',$cpdScript);
/* END MOD */


    $link = new moodle_url('/mod/certificate/view.php?id='.$cm->id.'&action=get');
    $button = new single_button($link, $linkname);
    if ($certificate->delivery != 1) {
        $button->add_action(new popup_action('click', $link, 'view' . $cm->id, array('height' => 1024, 'width' => 768)));
    }

    echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));

    echo html_writer::tag('output', '', array('id' => 'certificate_feedback'));

    echo "</div>";
    echo $OUTPUT->footer($course);
    exit;
} else { // Output to pdf


// No debugging here, sorry.
$CFG->debugdisplay = 0;
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');

    $filename = certificate_get_certificate_filename($certificate, $cm, $course) . '.pdf';

    // PDF contents are now in $file_contents as a string.
    $filecontents = $pdf->Output('', 'S');

    if ($certificate->savecert == 1) {
        certificate_save_pdf($filecontents, $certrecord->id, $filename, $context->id);
    }

    if ($certificate->delivery == 0) {
        // Open in browser.
        send_file($filecontents, $filename, 0, 0, true, false, 'application/pdf');
    } elseif ($certificate->delivery == 1) {
        // Force download.
        send_file($filecontents, $filename, 0, 0, true, true, 'application/pdf');
    } elseif ($certificate->delivery == 2) {
        certificate_email_student($course, $certificate, $certrecord, $context, $filecontents, $filename);
        // Open in browser after sending email.
        send_file($filecontents, $filename, 0, 0, true, false, 'application/pdf');
    }
}
exit;