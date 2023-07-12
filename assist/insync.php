<?php
require_once('../config.php');

require_login();
$valid = is_siteadmin();

if (!$valid) {
    $idnumber = optional_param('id', '', PARAM_ALPHANUMEXT);
    switch ($idnumber) {
        case "aa_surgeon":
            $valid = user_is_in_cohort($idnumber);
            break;

    }

    // avant assist 'full access' switch
    if (isset($CFG->aa_fullaccess) && $CFG->aa_fullaccess == true && !$valid) {
        $valid = user_is_in_cohort('aurora');
    }

}

if (!$valid) {
    @header($_SERVER['SERVER_PROTOCOL'] . ' 401 Access Denied');
    die("Unauthorized");
}

define('SECRET',    '7b83339a-c05f-414b-9ec5-eb69abfbd0da');
define('ENDPOINT',  'https://surgeonfeedback.insync.com.au/order/');
define('TIMESTAMP', time() + 300);

function user_is_in_cohort($idnumber) {
global $DB, $USER;
    $sql = <<<SQL
SELECT count(1) FROM {cohort_members} m
INNER JOIN {cohort} c ON c.id = m.cohortid
WHERE c.idnumber = ?
AND m.userid = ?
SQL;
    return $DB->count_records_sql($sql, [$idnumber, $USER->id]) > 0;
}

function sign_url($url, $signing_key, $expiry_timestamp) {
    $expiry_binary      = integer_to_binary($expiry_timestamp);
    $message            = concatenate($url, $signing_key, $expiry_binary);
    $hash               = sha256($message);
    $signature          = concatenate($hash, $expiry_binary);
    $encoded_signature  = base64url_encode($signature);
    $query_char         = strpos($url,"?")===false ? "?" : "&";

    return concatenate($url, $query_char, "signature=", $encoded_signature);
}

function base64url_encode($data) {
    $b64 = base64_encode($data);
    if ($b64 === false) {
        return false;
    }
    $url = strtr($b64, '+/', '-_');
    return rtrim($url, '=');
}

function sha256($value) {
    return hash('sha256', $value, true);
}

function integer_to_binary($value) {
    return pack('q',$value);
}

function concatenate(...$params) {
    return implode($params);
}

$url = sign_url(ENDPOINT, SECRET, TIMESTAMP);

@header("X-Redirect-By: Moodle Insync");
@header($_SERVER['SERVER_PROTOCOL'] . ' 303 See Other');
@header('Location: '.$url);