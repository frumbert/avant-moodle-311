<?php
$string['pluginname'] = 'Token Login';
$string['auth_tokendescription'] = 'Allows a user to either login to an existing acocunt or to create a new account using a token';

$string['autologinfailure'] = 'Your account appears to have been created ok but there was a problem automatically signing you in. Please try logging in manually or use the site contact details to request help.';

$string['createaccountinstructions'] = 'Enter your email address and token code. A password will be emailed to you.';

$string['nameentryinstructions'] = 'The name you enter below will appear on your certificate when you complete a course. Please be <strong>sure</strong> to correctly enter your real names.';

$string['signupfailure'] = 'There was a problem signing you up. Please use the site contact details form to have an account created for you.';

$string['registerredirect'] = 'Redirect url';
$string['registerredirect_desc'] = 'Alternate URL to load to after a successful registration.';

$string['logouturl'] = 'Logout url';
$string['logouturl_desc'] = 'Redirect to this url after logging out';

$string['signup_tokencode'] = 'Access token';
$string['signup_email'] = 'Email';

$string['signup_registerusing'] = 'Register';
$string['login_existingusers'] = 'Login';

$string['signup_tokencode_desc'] = 'Token code description';
$string['signup_missingtoken'] = 'You need to enter your token';

$string['signup_token_expired'] = 'Sorry, this token has expired';

$string['signup_passwordemailed'] = 'This will be emailed to you on submission';

$string['auth_token_noemail'] = 'Tried to send you an email but failed!';

$string['signup_userregoemail'] = 'Hi {$a->firstname},


Someone (probably you) has registered a new acount on the
site \'{$a->sitename}\'. The account is ready to use, and you
can log in again using these details:

    URL: {$a->link}
    Username: {$a->username}
    Password: {$a->password}

Cheers from the \'{$a->sitename}\' administrator,
{$a->signoff}';

/*
$string['signup_userregoemail_config_enabled'] = 'Signup email enabled?';
$string['signup_userregoemail_config'] = 'Signup email message';
$string['signup_userregoemail_config_desc'] = 'Possible merge field entries:
{$a->firstname} - the users first name
{$a->lastname} - the users last name
{$a->username} - the users login
{$a->password} - the users password
{$a->link} - a link to the site login page
{$a->sitename} - the name of this web site
{$a->signoff} - the system email signoff';
*/