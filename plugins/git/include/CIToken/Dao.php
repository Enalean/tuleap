<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git\CIToken;

use \DataAccessObject;

class Dao extends DataAccessObject
{
    public function getTokenForRepositoryId($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $sql = "SELECT ci_token FROM plugin_git WHERE repository_id=$repository_id";
        return $this->retrieve($sql);
    }

    public function updateTokenForRepositoryId($repository_id, $new_token)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $new_token     = $this->da->quoteSmart($new_token);
        $sql = "UPDATE plugin_git SET ci_token=$new_token WHERE repository_id=$repository_id";
        return $this->update($sql);
    }
}
