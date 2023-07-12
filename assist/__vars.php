<?php
header('content-type: text/plain');

$vars = ['AVANT_EMAIL_HOST', 'AVANT_EMAIL_PORT', 'AVANT_EMAIL_USERNAME', 'AVANT_EMAIL_PASSWORD', 'AVANT_FROM_ADDR', 'AVANT_REPLY_TO_ADDR'];

foreach ($vars as $var) {
        echo $var, '=>', getenv($var), PHP_EOL;
}