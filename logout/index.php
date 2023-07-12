<?php
require_once('../config.php');
$url = new moodle_url('/login/logout.php', array('sesskey'=>sesskey()));
redirect($url);