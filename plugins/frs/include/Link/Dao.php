<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\FRS\Link;

use DataAccessObject;

class Dao extends DataAccessObject
{
    public function saveLink($release_id, $artifact_id)
    {
        $release_id  = $this->da->escapeInt($release_id);
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "REPLACE INTO plugin_frs_release_artifact (release_id, artifact_id)
                VALUES ($release_id, $artifact_id)";

        return $this->update($sql);
    }

    public function deleteLink($release_id)
    {
        $release_id  = $this->da->escapeInt($release_id);

        $sql = "DELETE FROM plugin_frs_release_artifact
                WHERE release_id = $release_id";

        return $this->update($sql);
    }

    public function searchLinkedArtifactForRelease($release_id)
    {
        $release_id  = $this->da->escapeInt($release_id);

        $sql = "SELECT *
                FROM plugin_frs_release_artifact
                WHERE release_id = $release_id";

        return $this->retrieveFirstRow($sql);
    }
}
