<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All rights reserved
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

namespace Tuleap\SVN\ViewVC;

use DataAccessObject;

class AccessHistoryDao extends DataAccessObject
{
    public function incrementBrowseView($repository_id, $user_id, $day)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $user_id       = $this->da->escapeInt($user_id);
        $day           = $this->da->escapeInt($day);

        $sql = "INSERT INTO plugin_svn_full_history (repository_id, user_id, day, svn_browse_operations)
                VALUES ($repository_id, $user_id, $day, 1)
                ON DUPLICATE KEY UPDATE svn_browse_operations = svn_browse_operations+1";

        return $this->update($sql);
    }
}
