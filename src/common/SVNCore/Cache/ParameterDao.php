<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\SVNCore\Cache;

use DataAccessObject;

class ParameterDao extends DataAccessObject
{
    public function search()
    {
        $sql = "SELECT * FROM svn_cache_parameter";
        return $this->retrieve($sql);
    }

    public function save($maximum_credentials, $lifetime)
    {
        $maximum_credentials = $this->getDa()->quoteSmart($maximum_credentials);
        $lifetime            = $this->getDa()->quoteSmart($lifetime);

        $sql = "REPLACE INTO svn_cache_parameter(name, value)
                VALUES ('maximum_credentials' , $maximum_credentials), ('lifetime', $lifetime)";
        return $this->update($sql);
    }
}
