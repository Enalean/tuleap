<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Widget;

use Tuleap\DB\DataAccessObject;

class MyProjectsDao extends DataAccessObject
{
    public function searchProjectsUserIsMemberOf(int $user_id, string $order): ?array
    {
        $sql = "
            SELECT `groups`.group_id, `groups`.group_name, `groups`.unix_group_name, `groups`.status, `groups`.access, user_group.admin_flags, `groups`.icon_codepoint
            FROM `groups`
            JOIN user_group USING (group_id)
            WHERE user_group.user_id = ?
            AND `groups`.status = 'A'
            ORDER BY $order
        ";

        return $this->getDB()->run($sql, $user_id);
    }
}
