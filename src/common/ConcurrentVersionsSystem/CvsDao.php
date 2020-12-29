<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ConcurrentVersionsSystem;

use Tuleap\DB\DataAccessObject;

class CvsDao extends DataAccessObject
{
    /**
     * @return array|null
     * @psalm-return array{"revision": string, "description": string, "whoid": int, "comm_when": string}|null
     */
    public function searchCommit(int $commit_id, string $project_unix_name): ?array
    {
        $sql = "SELECT revision,
                    description,
                    cvs_commits.whoid,
                    cvs_commits.comm_when
                FROM cvs_checkins INNER JOIN cvs_repositories ON (cvs_checkins.repositoryid = cvs_repositories.id)
                    INNER JOIN cvs_descs ON (cvs_checkins.descid = cvs_descs.id)
                    INNER JOIN cvs_commits ON (cvs_checkins.commitid = cvs_commits.id)
                WHERE commitid = ?
                  AND repository = ?";

        return $this->getDB()->row(
            $sql,
            $commit_id,
            $this->getRepositoryNameFromProjectUnixName($project_unix_name)
        );
    }

    private function getRepositoryNameFromProjectUnixName(string $project_unix_name): string
    {
        return '/cvsroot/' . $project_unix_name;
    }
}
