<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Sidebar;

use Tuleap\DB\DataAccessObject;

final class MilestoneDao extends DataAccessObject implements RetrieveMilestonesWithSubMilestones
{
    public function retrieveMilestonesWithSubMilestones(int $project_id, int $parent_tracker_id): array
    {
        $sql = <<<SQL
               SELECT parent.id as parent_id,
                      parent.tracker_id as parent_tracker,
                      parent.last_changeset_id as parent_changeset,
                      parent.submitted_by as parent_submitted_by,
                      parent.submitted_on as parent_submitted_on,
                      parent.use_artifact_permissions as parent_use_artifact_permissions,
                      parent.per_tracker_artifact_id as parent_per_tracker_artifact_id,

                      submilestone.id as submilestone_id,
                      submilestone.tracker_id as submilestone_tracker,
                      submilestone.last_changeset_id as submilestone_changeset,
                      submilestone.submitted_by as submilestone_submitted_by,
                      submilestone.submitted_on as submilestone_submitted_on,
                      submilestone.use_artifact_permissions as submilestone_use_artifact_permissions,
                      submilestone.per_tracker_artifact_id as submilestone_per_tracker_artifact_id

               FROM tracker_artifact AS parent

                   -- Get parent
               INNER JOIN tracker_changeset AS Changeset_Parent on (parent.last_changeset_id = Changeset_Parent.id)
               INNER JOIN (
                   tracker_semantic_status as SemanticStatus_Parent
                   INNER JOIN tracker_changeset_value AS ChangesetValue_Parent ON (SemanticStatus_Parent.field_id = ChangesetValue_Parent.field_id)
                   INNER JOIN tracker_changeset_value_list AS ChangesetValueList_Parent ON (
                       ChangesetValue_Parent.id = ChangesetValueList_Parent.changeset_value_id
                       AND ChangesetValueList_Parent.bindvalue_id = SemanticStatus_Parent.open_value_id
                   )
               ) ON (parent.tracker_id = SemanticStatus_Parent.tracker_id AND Changeset_Parent.id = ChangesetValue_Parent.changeset_id)

                   -- Get submilestones
               LEFT JOIN tracker_field as TrakcerField ON (
                   parent.tracker_id = TrakcerField.tracker_id
                   AND TrakcerField.formElement_type = 'art_link'
               )
               LEFT JOIN tracker_changeset_value AS CV ON (
                   CV.field_id = TrakcerField.id
                   AND CV.changeset_id = parent.last_changeset_id
               )
               LEFT JOIN tracker_changeset_value_artifactlink AS ChangesetValueArtlink ON (
                   ChangesetValueArtlink.changeset_value_id = CV.id
                   AND ChangesetValueArtlink.nature = '_is_child'
               )
               LEFT JOIN (
                   tracker_artifact AS submilestone
                   INNER JOIN tracker AS submilestone_tracker ON (
                       submilestone_tracker.id = submilestone.tracker_id
                       AND submilestone_tracker.deletion_date IS NULL
                   )
                   INNER JOIN plugin_agiledashboard_planning AS planning ON (
                       planning.planning_tracker_id = submilestone.tracker_id AND planning.group_id = ?
                   )
                   INNER JOIN tracker_changeset AS Changeset_Sub ON (submilestone.last_changeset_id = Changeset_Sub.id)
                   INNER JOIN (
                       tracker_semantic_status AS SemanticStatus_Sub
                       INNER JOIN tracker_changeset_value AS ChangesetValue_Sub ON (SemanticStatus_Sub.field_id = ChangesetValue_Sub.field_id)
                       INNER JOIN tracker_changeset_value_list AS ChangesetValueList_Sub ON (
                           ChangesetValue_Sub.id = ChangesetValueList_Sub.changeset_value_id
                           AND ChangesetValueList_Sub.bindvalue_id = SemanticStatus_Sub.open_value_id
                       )
                   ) ON (submilestone.tracker_id = SemanticStatus_Sub.tracker_id AND Changeset_Sub.id = ChangesetValue_Sub.changeset_id)
               ) ON (submilestone.id = ChangesetValueArtlink.artifact_id)

               WHERE parent.tracker_id = ?

               ORDER BY parent.id DESC, submilestone.id DESC
               ;
               SQL;

        return $this->getDB()->run($sql, $project_id, $parent_tracker_id);
    }
}
