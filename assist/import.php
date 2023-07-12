<?php

require_once("../config.php");
require_once ($CFG->dirroot . '/cohort/lib.php');

raise_memory_limit(MEMORY_HUGE);
core_php_time_limit::raise(300);

if (($handle = fopen("newusers.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $idnumber = '';
        list($memberid, $customerid, $classification) = $data;

        switch (strtolower($classification)) {
            case "surgical": $idnumber = 'aa_surgeon'; break;
            case "gp": $idnumber = 'aa_gp'; break;
        }

        if ($cohort = $DB->get_record('cohort', ['idnumber' => $idnumber])) {

            $pref = $DB->get_record('user_preferences', ['name'=>'memberid','value'=>$memberid]);
            $user = $DB->get_record('user', ['username'=>$customerid]);

            if ($user) {
                set_user_preference('memberid', $memberid, $user);
            } else {

                $user = new stdClass;
                $user->username = $customerid;
                $user->password = 'not cached'; // hash_internal_user_password($customerid . 'Something salty 1234'); // the CLASS needs a password, but it is NOT-CACHED
                $user->firstname = 'firstname';
                $user->lastname = 'lastname';
                $user->email = 'email';
                $user->country = 'AU';
                $user->auth = 'aurora';
                $user->mnethostid = $CFG->mnet_localhost_id;
                $user->confirmed = 1;
                $user->department = '';

                $user->id = $DB->insert_record('user', $user);
                $user = $DB->get_record('user', array('id' => $user->id));
                \core\event\user_created::create_from_userid($user->id)->trigger();

                set_user_preference('memberid', $memberid, $user);

            }

            cohort_add_member($cohort->id, $user->id);
        }

    }
    fclose($handle);
}
echo "end";