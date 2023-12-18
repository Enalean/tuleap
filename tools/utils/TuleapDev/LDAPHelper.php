<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace TuleapDev\TuleapDev;

use LDAP\Connection;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class LDAPHelper
{
    /**
     * @return Ok<Connection>|Err<Fault>
     */
    public static function getLdapConnection(): Ok|Err
    {
        $ds = ldap_connect(\ForgeConfig::get('sys_ldap_server'));
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
        if (! @ldap_bind($ds, 'cn=Manager,dc=tuleap,dc=local', getenv('LDAP_MANAGER_PASSWORD'))) {
            return Result::err(Fault::fromMessage('Unable to bind to LDAP server for Manager: ' . ldap_error($ds)));
        }
        return Result::ok($ds);
    }

    /**
     * @return Ok<array>|Err<Fault>
     */
    public static function getUser(Connection $ds, string $login): Ok|Err
    {
        $login_search = \ForgeConfig::get('sys_ldap_uid') . '=' . ldap_escape($login, '', LDAP_ESCAPE_FILTER);
        $sr           = ldap_search($ds, \ForgeConfig::get('sys_ldap_dn'), $login_search);
        $entries      = ldap_get_entries($ds, $sr);
        if ($entries['count'] !== 1) {
            return Result::err(Fault::fromMessage(sprintf('There is no LDAP entry that corresponds to `%s` login', $login)));
        }

        return Result::ok($entries[0]);
    }
}
