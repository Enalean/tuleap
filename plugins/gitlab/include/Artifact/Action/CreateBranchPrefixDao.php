<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Artifact\Action;

use Tuleap\DB\DataAccessObject;

class CreateBranchPrefixDao extends DataAccessObject implements SaveIntegrationBranchPrefix
{
    #[\Override]
    public function setCreateBranchPrefixForIntegration(int $integration_id, string $prefix): void
    {
        $sql = 'REPLACE INTO plugin_gitlab_repository_integration_create_branch_prefix
            (integration_id, create_branch_prefix)
            VALUES (?, ?)';

        $this->getDB()->run($sql, $integration_id, $prefix);
    }

    public function searchCreateBranchPrefixForIntegration(int $integration_id): string
    {
        $sql = 'SELECT create_branch_prefix
            FROM plugin_gitlab_repository_integration_create_branch_prefix
            WHERE integration_id = ?';

        return $this->getDB()->single($sql, [$integration_id]) ?: '';
    }

    public function deleteIntegrationPrefix(int $integration_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_create_branch_prefix',
            [
                'integration_id' => $integration_id,
            ]
        );
    }
}
