<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
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

require_once 'common/dao/include/DataAccessObject.class.php';

class Testing_Campaign_CampaignDao extends DataAccessObject {

    public function searchByProjectId($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT * FROM plugin_testing_campaign WHERE project_id = $project_id";

        return $this->retrieve($sql);
    }

    public function searchById($id) {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT * FROM plugin_testing_campaign WHERE id = $id";

        return $this->retrieve($sql);
    }

    public function create($project_id, $name, $release_id) {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($name);
        $release_id = $this->da->escapeInt($release_id);

        if (! $release_id) {
            $release_id = 'NULL';
        }

        $sql = "INSERT INTO plugin_testing_campaign(project_id, name, product_version_id)
                VALUES ($project_id, $name, $release_id)";

        return $this->updateAndGetLastId($sql);
    }

    public function deleteById($id) {
        $id = $this->da->escapeInt($id);

        $sql = "DELETE FROM plugin_testing_campaign WHERE id = $id";

        return $this->update($sql);
    }
}
