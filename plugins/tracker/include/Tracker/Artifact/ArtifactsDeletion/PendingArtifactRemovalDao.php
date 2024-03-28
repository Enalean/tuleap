<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use Tuleap\DB\DataAccessObject;

class PendingArtifactRemovalDao extends DataAccessObject
{
    public function addArtifactToPendingRemoval($artifact_id)
    {
        $sql = 'INSERT INTO plugin_tracker_artifact_pending_removal
                  SELECT * FROM tracker_artifact
                  WHERE id = ?';

        $this->getDB()->run($sql, $artifact_id);
    }

    public function getPendingArtifactById($artifact_id)
    {
        $sql = 'SELECT * FROM plugin_tracker_artifact_pending_removal
                  WHERE id = ?';

        return $this->getDB()->row($sql, $artifact_id);
    }

    public function removeArtifact($artifact_id): void
    {
        $tables_to_clean_with_join = [
            'tracker_changeset' => 'tracker_changeset.artifact_id = plugin_tracker_artifact_pending_removal.id',
            'tracker_changeset_comment' => 'tracker_changeset_comment.changeset_id = tracker_changeset.id',
            'tracker_changeset_comment_fulltext' => 'tracker_changeset_comment_fulltext.comment_id = tracker_changeset_comment.id',
            'tracker_changeset_incomingmail' => 'tracker_changeset_incomingmail.changeset_id = tracker_changeset.id',
            'tracker_changeset_value' => 'tracker_changeset_value.changeset_id = tracker_changeset.id',
            'tracker_changeset_value_artifactlink' => 'tracker_changeset_value_artifactlink.changeset_value_id = tracker_changeset_value.id',
            'tracker_changeset_value_computedfield_manual_value' => 'tracker_changeset_value_computedfield_manual_value.changeset_value_id = tracker_changeset_value.id',
            'tracker_changeset_value_date' => 'tracker_changeset_value_date.changeset_value_id = tracker_changeset_value.id',
            'tracker_changeset_value_file' => 'tracker_changeset_value_file.changeset_value_id = tracker_changeset_value.id',
            'tracker_changeset_value_float' => 'tracker_changeset_value_float.changeset_value_id = tracker_changeset_value.id',
            'tracker_changeset_value_int' => 'tracker_changeset_value_int.changeset_value_id = tracker_changeset_value.id',
            'tracker_changeset_value_list' => 'tracker_changeset_value_list.changeset_value_id = tracker_changeset_value.id',
            'tracker_changeset_value_openlist' => 'tracker_changeset_value_openlist.changeset_value_id = tracker_changeset_value.id',
            'tracker_changeset_value_permissionsonartifact' => 'tracker_changeset_value_permissionsonartifact.changeset_value_id = tracker_changeset_value.id',
            'tracker_changeset_value_text' => 'tracker_changeset_value_text.changeset_value_id = tracker_changeset_value.id',
        ];

        $tables = \Psl\Str\join(array_keys($tables_to_clean_with_join), ', ');
        $joins  = '';
        foreach ($tables_to_clean_with_join as $table => $join) {
            $joins = $joins . sprintf('LEFT JOIN %s ON (%s) ', $table, $join);
        }

        $sql = "DELETE plugin_tracker_artifact_pending_removal, $tables
                FROM plugin_tracker_artifact_pending_removal $joins
                WHERE plugin_tracker_artifact_pending_removal.id = ?";

        $this->getDB()->run($sql, $artifact_id);
    }
}
