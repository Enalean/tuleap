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

namespace Tuleap\Roadmap;

use Tuleap\DB\DataAccessObject;

class NatureForRoadmapDao extends DataAccessObject
{
    /**
     * @psalm-return null|array{id: int, nature: string}[]
     */
    public function searchForwardLinksHavingSemantics(int $artifact_id): ?array
    {
        $sql = "SELECT artlink.artifact_id AS id, IFNULL(artlink.nature, '') AS nature
                FROM tracker_artifact AS parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = linked_art.tracker_id AND t.deletion_date IS NULL)
                    INNER JOIN tracker_semantic_title               AS title      ON (title.tracker_id = t.id)
                    INNER JOIN tracker_semantic_timeframe           AS timeframe  ON (t.id = timeframe.tracker_id)
                    INNER JOIN groups ON (groups.group_id = t.group_id AND groups.status = 'A')
                WHERE parent_art.id  = ?";

        return $this->getDB()->run($sql, $artifact_id);
    }
}
