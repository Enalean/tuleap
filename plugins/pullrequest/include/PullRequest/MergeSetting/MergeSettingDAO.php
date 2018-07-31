<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\MergeSetting;

use Tuleap\DB\DataAccessObject;

class MergeSettingDAO extends DataAccessObject
{
    public function getMergeSettingByRepositoryID($repository_id)
    {
        return $this->getDB()->row(
            'SELECT merge_commit_allowed FROM plugin_pullrequest_merge_setting WHERE repository_id = ?',
            $repository_id
        );
    }

    public function duplicateRepositoryMergeSettings($base_repository_id, $forked_repository_id)
    {
        $sql = "INSERT INTO plugin_pullrequest_merge_setting (repository_id, merge_commit_allowed)
                SELECT  ?, merge_commit_allowed
                FROM plugin_pullrequest_merge_setting
                WHERE repository_id = ?";

        return $this->getDB()->single($sql, [$forked_repository_id, $base_repository_id]);
    }
}
