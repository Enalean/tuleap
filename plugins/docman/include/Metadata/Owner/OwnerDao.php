<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\Metadata\Owner;

use Project;
use Tuleap\DB\DataAccessObject;

final class OwnerDao extends DataAccessObject implements OwnerData
{
    private const int LIMIT_OF_AUTOCOMPLETE = 15;

    /**
     * @psalm-return null|array{array{user_id: string,user_name: string, realname: string, has_custom_avatar: bool}}
     */
    #[\Override]
    public function getDocumentOwnerOfProjectForAutocomplete(Project $project, string $name_to_search): ?array
    {
        $name = $this->getDB()->escapeLikeValue($name_to_search);
        $sql  = 'SELECT DISTINCT user.user_id, user.user_name, user.realname, user.has_custom_avatar
                FROM user JOIN plugin_docman_item on user.user_id = plugin_docman_item.user_id
                WHERE group_id = ?
                AND (user.realname LIKE ?
                    OR user.user_name LIKE ? )
                LIMIT ?';

        return $this->getDB()->safeQuery($sql, [(int) $project->getID(), '%' . $name . '%', '%' . $name . '%', self::LIMIT_OF_AUTOCOMPLETE]);
    }
}
