<?php
/**
* Copyright (c) Enalean, 2021-Present. All Rights Reserved.
*
* This file is a part of Tuleap.
*
* Tuleap is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Tuleap is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

declare(strict_types=1);

require_once __DIR__ . '/../../../../src/www/include/pre.php';
require_once __DIR__ . '/../../../../tests/rest/vendor/autoload.php';
require_once __DIR__ . '/../../include/ldapPlugin.php';

$ldap = ldap_connect('ldap://ldap');
ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
ldap_bind($ldap, 'cn=Manager,dc=tuleap,dc=local', 'DumpPass4Tests');
ldap_add(
    $ldap,
    'cn=mygroup,ou=groups,dc=tuleap,dc=local',
    [
        'cn' => 'mygroup',
        'gidnumber' => 500,
        'objectClass' => ['top', 'posixGroup'],
    ]
);

\Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB()
    ->run(
        'INSERT INTO plugin_ldap_project_group(group_id, ldap_group_dn, synchro_policy, bind_option)
                SELECT group_id, "cn=mygroup,ou=groups,dc=tuleap,dc=local", "never", "preserve_members"
                FROM `groups`
                WHERE unix_group_name = "ldaptests"'
    );
