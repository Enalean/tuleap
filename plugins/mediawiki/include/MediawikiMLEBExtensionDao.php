<?php
/**
 * Copyright (c) Enalean 2015. All Rights Reserved.
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

class MediawikiMLEBExtensionDao extends DataAccessObject {

    public function saveMLEBActivationForProject($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "REPLACE INTO plugin_mediawiki_extension (project_id, extension_mleb)
                VALUES ($project_id, 1)";

        return $this->update($sql);
    }

    public function getProjectIdsEligibleToMLEBExtensionActivation() {
        $sql = "SELECT pmv.project_id
                FROM plugin_mediawiki_version pmv
                LEFT JOIN plugin_mediawiki_extension pme
                  ON pme.project_id = pmv.project_id
                WHERE (pme.project_id IS NULL OR  pme.extension_mleb = 0)
                  AND pmv.mw_version = '1.23'";

        return $this->retrieve($sql);
    }

    public function getMLEBUsageForProject($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT extension_mleb
                FROM plugin_mediawiki_extension
                WHERE project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }
}