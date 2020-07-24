<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Git;

use Tuleap\DB\DataAccessObject;

class GlobalParameterDao extends DataAccessObject
{
    /**
     * @return bool
     */
    public function isAuthorizedKeysFileManagedByTuleap()
    {
        $sql = 'SELECT * FROM plugin_git_global_parameters WHERE name = "authorized_keys_managed" AND value="tuleap"';

        $row = $this->getDB()->row($sql);

        return ! empty($row);
    }

    /**
     * @return bool
     */
    public function enableAuthorizedKeysFileManagementByTuleap()
    {
        $sql = 'INSERT INTO plugin_git_global_parameters(name, value) VALUES ("authorized_keys_managed", "tuleap")
                ON DUPLICATE KEY UPDATE value = "tuleap"';

        try {
            $this->getDB()->run($sql);
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }
}
