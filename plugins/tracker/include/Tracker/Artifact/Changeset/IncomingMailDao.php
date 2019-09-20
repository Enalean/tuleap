<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_Artifact_Changeset_IncomingMailDao extends DataAccessObject
{

    public function save($changeset_id, $raw_mail)
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $raw_mail     = $this->da->quoteSmart($raw_mail);

        $sql = "REPLACE INTO tracker_changeset_incomingmail (changeset_id, raw_mail)
                VALUES ($changeset_id, $raw_mail)";

        return $this->update($sql);
    }

    public function searchByArtifactId($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT M.*
                FROM tracker_changeset_incomingmail AS M
                    INNER JOIN tracker_changeset AS C ON (
                        C.id = M.changeset_id
                        AND C.artifact_id = $artifact_id
                    )";

        return $this->retrieve($sql);
    }
}
