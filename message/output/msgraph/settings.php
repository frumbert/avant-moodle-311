<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     message_msgraph
 * @category    admin
 * @copyright   2021 Daniel Neis <daniel@adapta.online>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // The userPrincipalName.
    $setting = new admin_setting_configtext('message_msgraph/userprincipalname',
        new lang_string('userprincipalname', 'message_msgraph'),
        new lang_string('userprincipalnamedesc', 'message_msgraph'), '', PARAM_TEXT);
    $settings->add($setting);

    // Client ID.
    $setting = new admin_setting_configtext('message_msgraph/clientid',
        new lang_string('clientid', 'message_msgraph'),
        new lang_string('clientiddesc', 'message_msgraph'), '', PARAM_TEXT);
    $settings->add($setting);

    // Client Secret.
    $setting = new admin_setting_configtext('message_msgraph/clientsecret',
        new lang_string('clientsecret', 'message_msgraph'),
        new lang_string('clientsecretdesc', 'message_msgraph'), '', PARAM_TEXT);
    $settings->add($setting);

    // Tenant.
    $setting = new admin_setting_configtext('message_msgraph/tenant',
        new lang_string('tenant', 'message_msgraph'),
        new lang_string('tenantdesc', 'message_msgraph'), '', PARAM_TEXT);
    $settings->add($setting);

    $settings->add(new admin_setting_description('message_msgraph/test',
                                                 get_string('test', 'message_msgraph'),
                                                 html_writer::link(new moodle_url('/message/output/msgraph/test.php'),
                                                                   get_string('testlink', 'message_msgraph'))
                                                ));
}
