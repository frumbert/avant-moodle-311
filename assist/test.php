<?php
define('SECRET',    'abcd1234');
define('ENDPOINT',  'https://example.com/restricted/');
define('TIMESTAMP', 1670399358);

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

$expected = "https://example.com/restricted/?signature=GnBT-EPW54RDsBP6PMmE4-4fDLWnLTqbeZRpVtOWOfl-RZBjAAAAAA";
echo assert($expected === $url);

/*
When:
    url              = "https://example.com/restricted/",
    signing_key      = "abcd1234",
    expiry_timestamp = 1670399358 as 64-bit signed integer little endian
Then:
    signed_url() returns 
                          https://example.com/restricted/?signature=GnBT-EPW54RDsBP6PMmE4-4fDLWnLTqbeZRpVtOWOfl-RZBjAAAAAA
*/