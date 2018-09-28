<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
        $sql = "INSERT INTO plugin_tracker_artifact_pending_removal
                  SELECT * FROM tracker_artifact
                  WHERE id = ?";

        $this->getDB()->run($sql, $artifact_id);
    }

    public function getPendingArtifactById($artifact_id)
    {
        $sql = "SELECT * FROM plugin_tracker_artifact_pending_removal
                  WHERE id = ?";

        return $this->getDB()->row($sql, $artifact_id);
    }

    public function removeArtifact($artifact_id)
    {
        $sql = "DELETE FROM plugin_tracker_artifact_pending_removal
                  WHERE id = ?";

        $this->getDB()->run($sql, $artifact_id);
    }
}
