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

namespace Tuleap\Tracker\Artifact;

final class PaginatedArtifactDao extends \Tuleap\DB\DataAccessObject
{
    public function searchPaginatedByListOfTrackerIds(array $tracker_ids, int $limit, int $offset): PaginatedArtifactRows
    {
        return $this->getDB()->tryFlatTransaction(function () use ($tracker_ids, $limit, $offset) {
            $ids_condition = \ParagonIE\EasyDB\EasyStatement::open()->in('?*', $tracker_ids);

            $total_size = $this->getDB()->cell(
                "SELECT count(*) FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id IN ($ids_condition))",
                ...$ids_condition->values()
            );
            if (! $total_size) {
                return new PaginatedArtifactRows([], 0);
            }

            $sql = "SELECT A.*, CVT.value AS title, CVT.body_format AS title_format
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id IN ($ids_condition))
                    LEFT JOIN (
                        tracker_changeset_value AS CV
                        INNER JOIN tracker_semantic_title as ST ON (CV.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id = CVT.changeset_value_id)
                    ) ON (A.last_changeset_id = CV.changeset_id)
                    ORDER BY T.id ASC, A.id ASC
                    LIMIT ? OFFSET ?";

            $params = [...$ids_condition->values(), $limit, $offset];

            return new PaginatedArtifactRows(
                $this->getDB()->run($sql, ...$params),
                $total_size,
            );
        });
    }

    public function searchPaginatedByListOfArtifactIds(array $artifact_ids, int $limit, int $offset): PaginatedArtifactRows
    {
        return $this->getDB()->tryFlatTransaction(function () use ($artifact_ids, $limit, $offset) {
            $ids_condition = \ParagonIE\EasyDB\EasyStatement::open()->in('?*', $artifact_ids);

            $total_size = $this->getDB()->cell(
                "SELECT count(*) FROM tracker_artifact AS A WHERE A.id IN ($ids_condition)",
                ...$ids_condition->values()
            );
            if (! $total_size) {
                return new PaginatedArtifactRows([], 0);
            }

            $sql = "SELECT A.*, CVT.value AS title, CVT.body_format AS title_format
                FROM tracker_artifact AS A
                    LEFT JOIN (
                        tracker_changeset_value AS CV
                        INNER JOIN tracker_semantic_title as ST ON (CV.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id = CVT.changeset_value_id)
                    ) ON (A.last_changeset_id = CV.changeset_id)
                    WHERE A.id IN ($ids_condition)
                    ORDER BY A.id ASC
                    LIMIT ? OFFSET ?";

            $params = [...$ids_condition->values(), $limit, $offset];

            return new PaginatedArtifactRows(
                $this->getDB()->run($sql, ...$params),
                $total_size,
            );
        });
    }
}
