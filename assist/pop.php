<?php
require_once('config.php');
require_once ($CFG->dirroot . '/cohort/lib.php');

define('AURORA_COHORT', 'aurora');

$users = $DB->get_records('user',['auth'=>'aurora']);
$c = 0;
if ($DB->record_exists('cohort', array('idnumber'=> AURORA_COHORT ))) {
    $cohortrow = $DB->get_record('cohort', array('idnumber' => AURORA_COHORT));
    foreach ($users as $user) {
        if (!$DB->record_exists('cohort_members', array('cohortid'=>$cohortrow->id, 'userid'=>$user->id))) {
            $c++;
            $record = new stdClass();
            $record->cohortid  = $cohortrow->id;
            $record->userid    = $user->id;
            $record->timeadded = time();
            $DB->insert_record('cohort_members', $record);
        }
    }
}

echo 'end - ', $c, ' records added';