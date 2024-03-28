<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Project;

use Tuleap\DB\DataAccessObject;

class GitlabRepositoryProjectDao extends DataAccessObject
{
    public function isGitlabRepositoryIntegratedInProject(int $integration_id, int $project_id): bool
    {
        $sql = 'SELECT NULL
                FROM plugin_gitlab_repository_integration
                WHERE id = ?
                    AND project_id = ?';

        $rows = $this->getDB()->run($sql, $integration_id, $project_id);

        return count($rows) > 0;
    }

    public function isArtifactClosureActionEnabledForRepositoryInProject(int $integration_id, int $project_id): bool
    {
        $sql = 'SELECT NULL
                FROM plugin_gitlab_repository_integration
                WHERE id = ?
                    AND project_id = ?
                    AND allow_artifact_closure = 1';

        $rows = $this->getDB()->run($sql, $integration_id, $project_id);

        return count($rows) > 0;
    }
}
