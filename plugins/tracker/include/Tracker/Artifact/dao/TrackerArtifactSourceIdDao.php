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
 *
 */

namespace Tuleap\Tracker\DAO;

use Tuleap\DB\DataAccessObject;

class TrackerArtifactSourceIdDao extends DataAccessObject
{
    /**
     * @param $source_platform
     * @param $tracker_id
     * @return array|null
     */
    public function getSourceArtifactIds($source_platform, $tracker_id)
    {
        $sql = "SELECT artifact_id, source_artifact_id, source_platform
                FROM plugin_tracker_source_artifact_id JOIN tracker_artifact ON (artifact_id = id)
                WHERE source_platform = ? AND tracker_id = ?";

        return $this->getDB()->run($sql, $source_platform, $tracker_id);
    }

    public function save($artifact_id, $source_artifact_id, $source_platform)
    {
        $sql = "INSERT IGNORE plugin_tracker_source_artifact_id (artifact_id, source_artifact_id, source_platform)
                VALUES (?, ?, ?)";

        return $this->getDB()->run($sql, $artifact_id, $source_artifact_id, $source_platform);
    }
}
