<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance\Migration\Admin;

use Tuleap\DB\DataAccessObject;

final class LegacyReadyToMigrateDao extends DataAccessObject implements ProjectReadyToBeMigratedVerifier
{
    public function searchProjectsUsingLegacyMediaWiki(): array
    {
        return $this->getDB()->run(
            'SELECT project.*, ongoing.is_error as ongoing_initialization_error
            FROM `groups` as project
            INNER JOIN plugin_mediawiki_database ON (project.group_id = plugin_mediawiki_database.project_id)
            LEFT JOIN plugin_mediawiki_standalone_ongoing_initializations AS ongoing ON (project.group_id = ongoing.project_id)
            WHERE project.status = ?
            ORDER BY project.group_name',
            \Project::STATUS_ACTIVE,
        );
    }

    #[\Override]
    public function isProjectReadyToBeMigrated(int $project_id): bool
    {
        return $this->getDB()->exists(
            'SELECT 1
            FROM `groups` as project
            INNER JOIN plugin_mediawiki_database ON (project.group_id = plugin_mediawiki_database.project_id)
            LEFT JOIN plugin_mediawiki_standalone_ongoing_initializations AS ongoing ON (project.group_id = ongoing.project_id)
            WHERE project.group_id = ?
              AND project.status = ?
              AND ongoing.project_id IS NULL',
            $project_id,
            \Project::STATUS_ACTIVE,
        );
    }
}
