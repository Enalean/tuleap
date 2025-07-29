<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

class ChangesetValueArtifactLinkDao extends DataAccessObject
{
    public function searchChangesetValues(int $field_id, int $changeset_id): array
    {
        return $this->getDB()->run(
            'SELECT cv.changeset_id, cv.has_changed, val.artifact_id, val.nature, t.item_name as keyword, t.group_id, a.tracker_id, a.last_changeset_id
                        FROM tracker_changeset_value_artifactlink AS val
                             INNER JOIN tracker_artifact AS a ON(a.id = val.artifact_id)
                             INNER JOIN tracker AS t ON(t.id = a.tracker_id AND t.deletion_date IS NULL)
                             INNER JOIN `groups` ON (t.group_id = `groups`.group_id)
                             INNER JOIN tracker_changeset_value AS cv
                             ON ( val.changeset_value_id = cv.id
                              AND cv.field_id = ?
                              AND cv.changeset_id = ?
                             )
                        WHERE `groups`.status = "A"
                        ORDER BY val.artifact_id',
            $field_id,
            $changeset_id
        );
    }
}
