<?php

// prod and uat are on the same server
// i just don't remember where they are in relation to each other

// find the prod folder
// $path_to_list = "../../cpd.avant.org.au/site/";
// // list folders and files in path
// $folders = scandir($path_to_list);
// $files = array();
// foreach ($folders as $file) {
//     if ($file!= '.' and $file!= '..') {
//         if (is_dir("$path_to_list/$file")) {
//             echo "Folder: ", $file;
//         } else {
//             echo "File: ", $file;
//         }
//     }
// }

// aha
$data = explode(PHP_EOL, file_get_contents('../../cpd.avant.org.au/site/config.php'));


// can't include config from the other folder because it url-checks before doing anything
require_once('config.php');
require_once ($CFG->dirroot . '/cohort/lib.php');

$grab = ['dbname','dbuser','dbpass'];

// so we duckpunch the database to prod
foreach ($data as $line) {
    if (strpos($line, "CFG->db")!==false) {
        list($name,$value) = explode('=',$line);
        $name = trim(substr($name, 6));
		if (in_array($name, $grab)) {
			$CFG->$name = $value;
            // echo "CFG->$name = $value <br>";
        }
    }
}
// now we need to somehow reset the $DB connection string
setup_DB();

// and now do the checking;

echo 'dbname=', $CFG->dbname, '<hr>';

define('AURORA_COHORT', 'aurora');

$users = $DB->get_records('user',['auth'=>'aurora']);
$c = 0;
if ($DB->record_exists('cohort', array('idnumber'=> AURORA_COHORT ))) {
    $cohortrow = $DB->get_record('cohort', array('idnumber' => AURORA_COHORT));
echo 'found cohort ', $cohortrow->id, ' there would be ', count($users) ;
    foreach ($users as $user) {
// echo 'checking ', $user->id, '<br>';
        if (!$DB->record_exists('cohort_members', array('cohortid'=>$cohortrow->id, 'userid'=>$user->id))) {
            echo '<li>would add user ', $user->username, ' to the auroras group</li>';
            $c++;

            // do the creation withOUT triggering any events
            $record = new stdClass();
            $record->cohortid  = $cohortrow->id;
            $record->userid    = $user->id;
            $record->timeadded = time();
//             $DB->insert_record('cohort_members', $record);
        
            // cohort_add_member($cohortrow->id, $user->id); // internally triggers cohort_member_added event
        }
    }
}

echo '<hr>', $c, ' added';