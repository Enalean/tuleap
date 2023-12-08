<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\SVN\Admin;

use Tuleap\SVNCore\Repository;
use DataAccessObject;

class ImmutableTagDao extends DataAccessObject
{
    public function save(Repository $repository, $path, $whitelist)
    {
        $repository_id = $this->da->escapeInt($repository->getId());
        $path          = $this->da->quoteSmart($path);
        $whitelist     = $this->da->quoteSmart($whitelist);

        $sql = "REPLACE INTO plugin_svn_immutable_tag (repository_id, paths, whitelist)
                VALUES ($repository_id, $path, $whitelist)";

        return $this->update($sql);
    }

    public function searchByRepositoryId($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $sql           = "SELECT *
                FROM plugin_svn_immutable_tag
                WHERE repository_id=$repository_id";

        return $this->retrieveFirstRow($sql);
    }
}
