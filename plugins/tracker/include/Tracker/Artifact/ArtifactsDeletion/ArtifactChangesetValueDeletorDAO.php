<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactChangesetValueDeletorDAO extends DataAccessObject
{
    public function cleanAllChangesetValueInTransaction(Artifact $artifact): void
    {
        $sql  = 'SELECT cvalue.changeset_id, changeset.id FROM tracker_changeset AS changeset
                INNER JOIN tracker_changeset_value AS cvalue ON changeset.id = cvalue.changeset_id WHERE artifact_id = ?';
        $rows = $this->getDB()->run($sql, $artifact->getId());

        foreach ($rows as $row) {
            $this->getDB()->delete('tracker_changeset_value_artifactlink', ['changeset_value_id' => $row['changeset_id']]);
            $this->getDB()->delete('tracker_changeset_value_computedfield_manual_value', ['changeset_value_id' => $row['changeset_id']]);
            $this->getDB()->delete('tracker_changeset_value_date', ['changeset_value_id' => $row['changeset_id']]);
            $this->getDB()->delete('tracker_changeset_value_file', ['changeset_value_id' => $row['changeset_id']]);
            $this->getDB()->delete('tracker_changeset_value_float', ['changeset_value_id' => $row['changeset_id']]);
            $this->getDB()->delete('tracker_changeset_value_int', ['changeset_value_id' => $row['changeset_id']]);
            $this->getDB()->delete('tracker_changeset_value_list', ['changeset_value_id' => $row['changeset_id']]);
            $this->getDB()->delete('tracker_changeset_value_openlist', ['changeset_value_id' => $row['changeset_id']]);
            $this->getDB()->delete('tracker_changeset_value_permissionsonartifact', ['changeset_value_id' => $row['changeset_id']]);
            $this->getDB()->delete('tracker_changeset_value_text', ['changeset_value_id' => $row['changeset_id']]);

            $sql = "DELETE tracker_changeset_comment, tracker_changeset_comment_fulltext
                    FROM tracker_changeset_comment
                    INNER JOIN tracker_changeset_comment_fulltext ON tracker_changeset_comment.id = tracker_changeset_comment_fulltext.comment_id
                    WHERE changeset_id = ?";
            $this->getDB()->run($sql, $row['changeset_id']);

            $this->getDB()->delete('tracker_changeset_incomingmail', ['changeset_id' => $row['id']]);

            $this->getDB()->delete('tracker_changeset_value', ['changeset_id' => $row['id']]);
        }

        $this->getDB()->delete('tracker_changeset', ['artifact_id' => $artifact->getId()]);
    }
}
