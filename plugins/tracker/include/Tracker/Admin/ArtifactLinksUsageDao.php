<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin;

use PDOException;
use Tuleap\DB\DataAccessObject;

class ArtifactLinksUsageDao extends DataAccessObject
{
    public function isProjectUsingArtifactLinkTypes(int $project_id): bool
    {
        $sql = 'SELECT NULL
                FROM plugin_tracker_projects_use_artifactlink_types
                WHERE project_id = ?';

        $rows = $this->getDB()->run($sql, $project_id);

        return count($rows) > 0;
    }

    public function activateForProject(int $project_id): bool
    {
        $sql = 'REPLACE INTO plugin_tracker_projects_use_artifactlink_types
                VALUES (?)';

        try {
            $this->getDB()->run($sql, $project_id);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }

    public function isTypeDisabledInProject(int $project_id, string $type_shortname): bool
    {
        $sql = 'SELECT NULL
                FROM plugin_tracker_projects_unused_artifactlink_types
                WHERE project_id = ?
                  AND type_shortname = ?';

        $rows = $this->getDB()->run($sql, $project_id, $type_shortname);

        return count($rows) > 0;
    }

    public function disableTypeInProject(int $project_id, string $type_shortname): void
    {
        $sql = 'REPLACE INTO plugin_tracker_projects_unused_artifactlink_types (project_id, type_shortname)
                VALUES (?, ?)';

        $this->getDB()->run($sql, $project_id, $type_shortname);
    }

    public function enableTypeInProject(int $project_id, string $type_shortname): void
    {
        $sql = 'DELETE FROM plugin_tracker_projects_unused_artifactlink_types
                WHERE project_id = ?
                  AND type_shortname = ?';

        $this->getDB()->run($sql, $project_id, $type_shortname);
    }

    public function duplicate(int $template_id, int $project_id): bool
    {
        if (
            ! $this->activateForProject($project_id) ||
            ! $this->duplicateTypesUsageInProject($template_id, $project_id)
        ) {
            return false;
        }
        return true;
    }

    private function duplicateTypesUsageInProject(int $template_id, int $project_id): bool
    {
        $sql = 'INSERT INTO plugin_tracker_projects_unused_artifactlink_types (project_id, type_shortname)
                SELECT ?, type_shortname
                FROM plugin_tracker_projects_unused_artifactlink_types
                WHERE project_id = ?';

        try {
            $this->getDB()->run($sql, $project_id, $template_id);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }
}
