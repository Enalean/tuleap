<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

/**
 * Manage ID sharing between tracker v3 and v5.
 *
 * If you need to insert something in tracker or artifact tables, then
 * you must use the corresponding method of this class to ensure that
 * there will be no id overlap between both tracker engines.
 *
 * Usage:
 * <pre>
 *     $id_sharing = new TrackerIdSharingDao();
 *     if ($id = $id_sharing->generateArtifactId()) {
 *         $sql = "INSERT INTO tracker_artifact
 *                 (id, tracker_id, submitted_by, submitted_on, use_artifact_permissions)
 *                 VALUES ($id, ........
 * </pre>
 */
class TrackerIdSharingDao extends DataAccessObject
{

    /**
     * Get a good tracker id.
     *
     * @return int (or false if something gone mad)
     */
    public function generateTrackerId()
    {
        $sql = "INSERT INTO tracker_idsharing_tracker VALUES ()";
        return $this->updateAndGetLastId($sql);
    }

    /**
     * Get a good artifact id.
     *
     * @return int (or false if something gone mad)
     */
    public function generateArtifactId()
    {
        $sql = "INSERT INTO tracker_idsharing_artifact VALUES ()";
        return $this->updateAndGetLastId($sql);
    }
}
