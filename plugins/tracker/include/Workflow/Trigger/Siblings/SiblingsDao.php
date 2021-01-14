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

namespace Tuleap\Tracker\Workflow\Trigger\Siblings;

use Tuleap\DB\DataAccessObject;

class SiblingsDao extends DataAccessObject
{
    /**
     * @psalm-return list<array{id:int, tracker_id:int, last_changeset_id:int, submitted_by:int, submitted_on:int, use_artifact_permissions:int, per_tracker_artifact_id: int}>
     */
    public function getSiblingsBasedOnDefinedTrackerHierarchy(int $artifact_id): array
    {
        $sql = "SELECT art_sibling.*
                FROM tracker_artifact parent_art
                    /* connect parent to its children (see getChildren) */
                    INNER JOIN tracker_field                        f_sibling            ON (f_sibling.tracker_id = parent_art.tracker_id AND f_sibling.formElement_type = 'art_link' AND f_sibling.use_it = 1)
                    INNER JOIN tracker_changeset_value              cv_sibling           ON (cv_sibling.changeset_id = parent_art.last_changeset_id AND cv_sibling.field_id = f_sibling.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink_sibling      ON (artlink_sibling.changeset_value_id = cv_sibling.id)
                    INNER JOIN tracker_artifact                     art_sibling          ON (art_sibling.id = artlink_sibling.artifact_id)
                    INNER JOIN tracker_hierarchy                    hierarchy_sibling    ON (hierarchy_sibling.child_id = art_sibling.tracker_id AND hierarchy_sibling.parent_id = parent_art.tracker_id)

                    /* connect child to its parent (see getParent) */
                    INNER JOIN tracker_field                        f_child         ON (f_child.tracker_id = parent_art.tracker_id AND f_child.formElement_type = 'art_link' AND f_child.use_it = 1)
                    INNER JOIN tracker_changeset_value              cv_child        ON (cv_child.changeset_id = parent_art.last_changeset_id AND cv_child.field_id = f_child.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink_child   ON (artlink_child.changeset_value_id = cv_child.id)
                    INNER JOIN tracker_artifact                     art_child       ON (art_child.id = artlink_child.artifact_id)
                    INNER JOIN tracker_hierarchy                    hierarchy_child ON (hierarchy_child.child_id = art_child.tracker_id AND hierarchy_child.parent_id = parent_art.tracker_id)
                WHERE art_child.id = ?
                  AND art_sibling.id != art_child.id";

        return $this->getDB()->run($sql, $artifact_id);
    }
}
