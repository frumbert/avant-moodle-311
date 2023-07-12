<?php

function weblink_auth($destination) {
global $CFG, $DB;

    // if no alternateloginurl is set we don't process anything
    $alternateloginurl = $CFG->alternateloginurl;
    if (empty($alternateloginurl)) return false;

    $url = parse_url($destination);
    $query = [];
    if (isset($url['query'])) parse_str($url['query'], $query);

    // check for AvantAssit modules (/assist/open.php calls /course/view.php?id=number)
    if (!isloggedin() || isguestuser()) {
        $paramid = empty($query['id']) ? 0 : $query['id'];
        if ($paramid > 0) {
            $alternateloginurl .= strpos($alternateloginurl, '?') === false ? '?' : '&amp;';
            if ($DB->record_exists('course', ['id'=>$paramid, 'idnumber'=>'aa_surgeon'])) {
                $CFG->alternateloginurl = $alternateloginurl . 'idnumber=aa_surgeon';
            } else if ($DB->record_exists('course', ['id'=>$paramid, 'idnumber'=>'aa_gp'])) {
                $CFG->alternateloginurl = $alternateloginurl . 'idnumber=aa_gp';
            }
        }
    }

    // we didnt do anything to the user
    return false;
}