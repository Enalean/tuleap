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

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\DB\DataAccessObject;

final class ReverseLinksDao extends DataAccessObject implements SearchReverseLinks
{
    #[\Override]
    public function searchReverseLinksById(int $artifact_id): array
    {
        $sql = <<<SQL
        SELECT DISTINCT source_artifact.id, artlink.nature as type
        FROM tracker_changeset_value_artifactlink AS artlink
                 JOIN tracker_changeset_value AS cv ON (cv.id = artlink.changeset_value_id)
                 JOIN tracker_artifact AS source_artifact ON (source_artifact.last_changeset_id = cv.changeset_id)
                 JOIN tracker ON (tracker.id = source_artifact.tracker_id AND tracker.deletion_date IS NULL)
                 JOIN `groups` AS project ON (project.group_id = tracker.group_id AND project.status = 'A')
        WHERE artlink.artifact_id = ?
SQL;

        $rows = $this->getDB()->q($sql, $artifact_id);

        return array_map(
            static fn(array $row) => new StoredLinkRow($row['id'], $row['type']),
            $rows
        );
    }
}
