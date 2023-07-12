<?PHP
unset($CFG);
global $CFG;
$CFG = new stdClass();


// $CFG->debug = 32767;
// $CFG->debugdisplay = true;

// gavine 4apr2016 - added for browser capture debugging for Tim
// $CFG->recordbrowser = true;

$CFG->site_is_public = false;

$CFG->dbtype    = 'mariadb'; // was mysqli
$CFG->dblibrary = 'native';
$CFG->dbhost    = '127.0.0.1';
$CFG->dbname    = 'prod23-9mar';
$CFG->dbname    = 'prod-11may';
$CFG->dbuser    = 'root';
$CFG->dbpass    = 'root';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbpersist' => false,
    'dbsocket'  => false,
    'dbport'    => '',
);

$CFG->tcpdf_owner_pass = 'AvantLearningCentre2022';
$CFG->passwordsaltmain = 'a_very_long_random_@3498A@#fda_string_of_characters#@6&*1sadf$#df34';

$CFG->wwwroot   = 'https://prod23.avant.test';

$CFG->dataroot  = __DIR__ . '/../moodledataprod23';
$CFG->directorypermissions = 0770;

$CFG->admin = 'admin';

// $CFG->disablescheduledbackups = true;
// $CFG->forced_plugin_settings = array('backup' => array ('backup_auto_active' => 0));

$CFG->customscripts = __DIR__ . '/customscripts';

// pick up mail config from environment, or fallback
// $CFG->smtpuser =  getenv('AVANT_EMAIL_USERNAME') ?: 'AKIA55FQWU36TLNEZGB5';
// $CFG->smtppass = getenv('AVANT_EMAIL_PASSWORD') ?: 'BJMGn9bm7tE2JgnpVakbbyiE6Wj+ssfGSQ8voeQtdZRO';
// $CFG->smtpsecure = 'tls';
// $CFG->smtphosts = getenv('AVANT_EMAIL_HOST') ?: 'email-smtp.ap-southeast-2.amazonaws.com';
// $CFG->smtphosts .= ':' . (getenv('AVANT_EMAIL_PORT') ?: '587');
// $CFG->smtpmaxbulk = 1;
// if (!empty(getenv('AVANT_REPLY_TO_ADDR'))) {
//     $CFG->noreplyaddress = getenv('AVANT_REPLY_TO_ADDR');
// }

// Avant Assist - when true, allow/report on users both the 'aa_surgeon','aa_gp' cohorts as well as the 'aurora' cohort
//              - when false, allow only users in the 'aa_surgeon', 'aa_gp' cohorts
$CFG->aa_fullaccess = true;

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
