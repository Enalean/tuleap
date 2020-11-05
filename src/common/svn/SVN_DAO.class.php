<?php
/**
* Copyright (c) Enalean, 2016. All rights reserved
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
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/
*/

class SVN_DAO extends \Tuleap\DB\DataAccessObject
{

    public function searchSvnRepositories()
    {
        $sql = "SELECT groups.*
                FROM groups
                    INNER JOIN service ON (service.group_id = groups.group_id AND service.short_name = 'svn')
                WHERE service.is_used = '1'
                  AND groups.status = 'A'";

        return $this->getDB()->run($sql);
    }
}
