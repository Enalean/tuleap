<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin;

use Tuleap\DB\DataAccessObject;

class NewLayoutDao extends DataAccessObject
{
    public function mustUserSeeTheModal(int $user_id): bool
    {
        $sql = "SELECT *
                FROM plugin_tracker_new_layout_modal_user
                WHERE user_id = ?
        ";

        $rows = $this->getDB()->run($sql, $user_id);

        return count($rows) > 0;
    }

    public function removeUserFromList(int $user_id): void
    {
        $this->getDB()->delete('plugin_tracker_new_layout_modal_user', ['user_id' => $user_id]);
    }
}
