<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Migration;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class KeepReverseCrossReferenceDAO extends DataAccessObject
{
    public function createCrossReferenceFromTrackerIDs(int $legacy_tracker_id, int $tracker_id): void
    {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($tracker_id, $legacy_tracker_id): void {
            $db->run(
                'DELETE cross_references.*
                FROM cross_references
                JOIN tracker_artifact ON (tracker_artifact.id = cross_references.target_id)
                WHERE tracker_artifact.tracker_id = ? AND cross_references.target_type = "plugin_tracker_artifact"',
                $tracker_id
            );

            $db->run(
                'INSERT INTO cross_references (created_at, user_id, source_type, source_keyword, source_id, source_gid, target_type, target_keyword, target_id, target_gid)
                SELECT cross_references.created_at, cross_references.user_id, cross_references.source_type, cross_references.source_keyword, cross_references.source_id, cross_references.source_gid, "plugin_tracker_artifact" AS target_type, cross_references.target_keyword, cross_references.target_id, cross_references.target_gid
                FROM cross_references
                JOIN artifact ON (artifact.artifact_id = cross_references.target_id)
                WHERE target_type = "artifact" AND artifact.group_artifact_id = ?',
                $legacy_tracker_id
            );
        });
    }
}
