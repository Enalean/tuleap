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

    public function updateTargetKeyword(string $old_keyword, string $keyword, int $group_id): void
    {
        $this->getDB()->run(
            "UPDATE cross_references SET target_keyword=? WHERE target_keyword=? and target_gid=?",
            $keyword,
            $old_keyword,
            $group_id
        );
    }

    public function updateSourceKeyword(string $old_keyword, string $keyword, int $group_id): void
    {
        $this->getDB()->run(
            "UPDATE cross_references SET source_keyword=? WHERE source_keyword=? and source_gid=?",
            $keyword,
            $old_keyword,
            $group_id
        );
    }

    public function deleteEntity(string $id, string $nature, int $group_id): void
    {
        $this->getDB()->run(
            "DELETE FROM cross_references
                WHERE (source_type = ? AND source_id = ? AND source_gid = ? )
                   OR (target_type = ? AND target_id = ? AND target_gid = ? )",
            $nature,
            $id,
            $group_id,
            $nature,
            $id,
            $group_id
        );
    }

    public function createDbCrossRef(CrossReference $cross_ref): bool
    {
        $this->getDB()->insert(
            'cross_references',
            [
                'created_at' => time(),
                'user_id' => (int) $cross_ref->userId,
                'source_type' => $cross_ref->insertSourceType,
                'source_keyword' => $cross_ref->sourceKey,
                'source_id' => $cross_ref->refSourceId,
                'source_gid' => $cross_ref->refSourceGid,
                'target_type' => $cross_ref->insertTargetType,
                'target_keyword' => $cross_ref->targetKey,
                'target_id' => $cross_ref->refTargetId,
                'target_gid' => $cross_ref->refTargetGid,
            ]
        );
        return true;
    }

    public function fullReferenceExistInDb(CrossReference $cross_ref): bool
    {
        return $this->getDB()->exists(
            "SELECT * FROM cross_references WHERE source_id=? AND
                          target_id=? AND
                          source_gid=? AND
                          target_gid=? AND
                          source_type=? AND
                          target_keyword=? AND
                          target_type=?",
            $cross_ref->refSourceId,
            $cross_ref->refTargetId,
            $cross_ref->refSourceGid,
            $cross_ref->refTargetGid,
            $cross_ref->insertSourceType,
            $cross_ref->targetKey,
            $cross_ref->insertTargetType
        );
    }

    public function existInDb(CrossReference $cross_ref): bool
    {
        return $this->getDB()->exists(
            "SELECT * from cross_references WHERE
                        source_id=? AND
                        target_id=? AND
                        source_gid=? AND
                        target_gid=? AND
                        source_type=? AND
                        target_type=?",
            $cross_ref->refSourceId,
            $cross_ref->refTargetId,
            $cross_ref->refSourceGid,
            $cross_ref->refTargetGid,
            $cross_ref->insertSourceType,
            $cross_ref->insertTargetType
        );
    }

    public function deleteCrossReference(CrossReference $cross_ref): bool
    {
        $sql = "DELETE FROM cross_references WHERE
                ( ( target_gid  = ? AND
                    target_id   = ? AND
                    target_type = ?
                  )
                  AND
                  ( source_gid  = ? AND
                    source_id   = ? AND
                    source_type = ?
                  )
                )
                OR
                ( ( target_gid  = ? AND
                    target_id   = ? AND
                    target_type = ?
                  )
                  AND
                  ( source_gid  = ? AND
                    source_id   = ? AND
                    source_type = ?
                  )
                )";

        $this->getDB()->run(
            $sql,
            $cross_ref->refTargetGid,
            $cross_ref->refTargetId,
            $cross_ref->getInsertTargetType(),
            $cross_ref->refSourceGid,
            $cross_ref->refSourceId,
            $cross_ref->getInsertSourceType(),
            $cross_ref->refTargetGid,
            $cross_ref->refTargetId,
            $cross_ref->getInsertTargetType(),
            $cross_ref->refSourceGid,
            $cross_ref->refSourceId,
            $cross_ref->getInsertSourceType()
        );

        return true;
    }

    public function deleteFullCrossReference(CrossReference $cross_ref): bool
    {
        $sql = "DELETE FROM cross_references WHERE
                ( ( target_gid     = ? AND
                    target_id      = ? AND
                    target_type    = ? AND
                    target_keyword = ?
                  )
                  AND
                  ( source_gid  = ? AND
                    source_id   = ? AND
                    source_type = ?
                  )
                )
                OR
                ( ( target_gid     = ? AND
                    target_id      = ? AND
                    target_type    = ? AND
                    target_keyword = ?
                  )
                  AND
                  ( source_gid  = ? AND
                    source_id   = ? AND
                    source_type = ?
                  )
                )";
        $this->getDB()->run(
            $sql,
            $cross_ref->refTargetGid,
            $cross_ref->refTargetId,
            $cross_ref->getInsertTargetType(),
            $cross_ref->targetKey,
            $cross_ref->refSourceGid,
            $cross_ref->refSourceId,
            $cross_ref->getInsertSourceType(),
            $cross_ref->refTargetGid,
            $cross_ref->refTargetId,
            $cross_ref->getInsertTargetType(),
            $cross_ref->targetKey,
            $cross_ref->refSourceGid,
            $cross_ref->refSourceId,
            $cross_ref->getInsertSourceType()
        );

        return true;
    }

    public function getReferenceByKeyword(string $keyword): ?array
    {
        $sql = "SELECT *
            FROM cross_references
            WHERE source_keyword = ?";

        return $this->getDB()->row($sql, $keyword);
    }
}
