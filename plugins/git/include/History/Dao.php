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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Git\History;

use DataAccessObject;

class Dao extends DataAccessObject
{
    public function insertGitPhpView($repository_id, $user_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $user_id       = $this->da->escapeInt($user_id);
        $time          = $this->da->escapeInt($_SERVER['REQUEST_TIME']);

        $sql = "INSERT INTO plugin_git_full_history (time, repository_id, user_id)
                VALUES ($time, $repository_id, $user_id)";

        return $this->update($sql);
    }
}
