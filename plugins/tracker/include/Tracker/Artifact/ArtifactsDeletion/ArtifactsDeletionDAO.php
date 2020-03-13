<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class ArtifactsDeletionDAO extends DataAccessObject
{
    public function searchNumberOfArtifactsDeletionsForUserInTimePeriod(
        $user_id,
        $timestamp
    ) {
        $sql = "SELECT COALESCE(sum(nb_artifacts_deleted), 0)
                FROM plugin_tracker_deleted_artifacts
                WHERE timestamp >= ?
                    AND user_id = ?
        ";

        return $this->getDB()->single($sql, [$timestamp, $user_id]);
    }

    public function recordDeletionForUser(
        $user_id,
        $timestamp
    ) {
        $sql = "INSERT INTO plugin_tracker_deleted_artifacts(timestamp, user_id, nb_artifacts_deleted)
                VALUES(?, ?, 1)
                ON DUPLICATE KEY UPDATE
                    nb_artifacts_deleted = nb_artifacts_deleted + 1;
        ";

        $this->getDB()->run($sql, $timestamp, $user_id);
    }

    public function deleteOutdatedArtifactsDeletions($limit_timestamp)
    {
        $sql = "DELETE FROM plugin_tracker_deleted_artifacts
                WHERE timestamp <= ?
        ";

        $this->getDB()->run($sql, $limit_timestamp);
    }
}
