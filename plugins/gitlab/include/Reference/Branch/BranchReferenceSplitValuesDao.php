<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Reference\Branch;

use Tuleap\DB\DataAccessObject;

class BranchReferenceSplitValuesDao extends DataAccessObject
{
    /**
     * @psalm-return array{repository_name: string, branch_name: string}
     */
    public function getAllBranchesSplitValuesInProject(int $project_id, string $value): ?array
    {
        $sql = "SELECT plugin_gitlab_repository_integration.name AS repository_name, plugin_gitlab_repository_integration_branch_info.branch_name
                FROM plugin_gitlab_repository_integration
                    INNER JOIN plugin_gitlab_repository_integration_branch_info
                        ON (plugin_gitlab_repository_integration.id = plugin_gitlab_repository_integration_branch_info.integration_id)
                WHERE plugin_gitlab_repository_integration.project_id = ?
                    AND CONCAT(name, '/', branch_name) = ?";

        return $this->getDB()->row($sql, $project_id, $value);
    }
}
