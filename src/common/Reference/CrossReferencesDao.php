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

namespace Tuleap\Reference;

use Tuleap\DB\DataAccessObject;

class CrossReferencesDao extends DataAccessObject
{
    public function searchTargetsOfEntity(string $entity_id, string $entity_type, int $entity_project_id): array
    {
        return $this->getDB()->run(
            "SELECT * FROM cross_references WHERE source_gid = ? AND source_type = ? AND source_id = ?",
            $entity_project_id,
            $entity_type,
            $entity_id,
        );
    }

    public function searchSourcesOfEntity(string $entity_id, string $entity_type, int $entity_project_id): array
    {
        return $this->getDB()->run(
            "SELECT * FROM cross_references WHERE target_gid = ? AND target_type = ? AND target_id = ?",
            $entity_project_id,
            $entity_type,
            $entity_id,
        );
    }

    public function updateReferencesWhenArtifactIsInTarget(int $source_id, int $destination_project_id): void
    {
        $this->getDB()->run(
            "UPDATE cross_references SET target_gid = ? WHERE target_id = ?",
            $destination_project_id,
            $source_id
        );
    }

    public function deleteReferencesWhenArtifactIsSource(int $id, string $nature, int $project_id): void
    {
        $this->getDB()->run(
            "DELETE FROM cross_references WHERE source_type = ? AND source_id = ? AND source_gid = ?",
            $nature,
            $id,
            $project_id
        );
    }
}
