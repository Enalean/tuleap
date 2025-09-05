<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Group;

use Tuleap\DB\DataAccessObject;

final class GroupLinkRepositoryIntegrationDAO extends DataAccessObject implements LinkARepositoryIntegrationToAGroup, CountIntegratedRepositories, VerifyRepositoryIntegrationsAlreadyLinked
{
    #[\Override]
    public function linkARepositoryIntegrationToAGroup(NewRepositoryIntegrationLinkedToAGroup $command): void
    {
        $this->getDB()->insert('plugin_gitlab_group_repository_integration', [
            'group_id' => $command->group_id,
            'integration_id' => $command->repository_integration_id,
        ]);
    }

    #[\Override]
    public function countIntegratedRepositories(GroupLink $group_link): int
    {
        return $this->getDB()->cell(
            'SELECT COUNT(*) FROM plugin_gitlab_group_repository_integration WHERE group_id = ?',
            $group_link->id
        );
    }

    #[\Override]
    public function isRepositoryIntegrationAlreadyLinkedToAGroup(int $integration_id): bool
    {
        $row = $this->getDB()->run(
            'SELECT NULL FROM plugin_gitlab_group_repository_integration WHERE integration_id = ?',
            $integration_id
        );

        return count($row) > 0;
    }
}
