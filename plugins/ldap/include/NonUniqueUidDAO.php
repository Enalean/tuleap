<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\LDAP;

final class NonUniqueUidDAO extends \Tuleap\DB\DataAccessObject
{
    /**
     * @psalm-return array<string,non-empty-list<array{user_name: string}>>
     */
    public function searchNonUniqueLdapUid(): array
    {
        $sql = "SELECT plugin_ldap_user.ldap_uid, user.user_name
                FROM plugin_ldap_user
                JOIN user ON (user.user_id = plugin_ldap_user.user_id)
                WHERE plugin_ldap_user.ldap_uid IN (
                    SELECT plugin_ldap_user.ldap_uid
                    FROM user
                        JOIN plugin_ldap_user ON (plugin_ldap_user.user_id = user.user_id)
                    WHERE user.status IN ('A', 'R')
                    GROUP BY ldap_uid
                    HAVING COUNT(ldap_uid) > 1
                )";

        return $this->getDB()->safeQuery(
            $sql,
            [],
            \PDO::FETCH_GROUP | \PDO::FETCH_ASSOC
        );
    }
}
