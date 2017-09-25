<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

require_once 'common/resource_restrictor/RestrictedResourceDao.class.php';

class MediawikiSiteAdminResourceRestrictorDao extends RestrictedResourceDao {

    public function getResourceAllowedProjectsTableName() {
        return 'plugin_mediawiki_site_restricted_features';
    }

    public function getResourceFieldName() {
        return 'feature';
    }

    public function isResourceRestricted($resource_id) {
        return true;
    }

    public function searchAllowedProjectsOnResource($resource_id) {
        $resource_id = $this->da->escapeInt($resource_id);
        $sql = "SELECT groups.*
                FROM groups
                  JOIN plugin_mediawiki_site_restricted_features mwf ON (mwf.project_id = groups.group_id)
                WHERE mwf.feature = $resource_id
                  AND status IN ('A', 's')";
        return $this->retrieve($sql);
    }

    public function setResourceRestricted($resource_id) {
        return false;
    }

    public function unsetResourceRestricted($resource_id) {
        return false;
    }

    public function isMediawiki123($resource_id, $wikiname) {
        $resource_id = $this->da->escapeInt($resource_id);
        $wikiname    = $this->da->quoteSmart($wikiname);
        $sql = "SELECT 1
                FROM plugin_mediawiki_site_restricted_features mwf
                  JOIN groups g ON (g.group_id = mwf.project_id)
                WHERE mwf.feature = $resource_id
                  AND g.unix_group_name = $wikiname";
        return $this->retrieve($sql)->count() > 0;
    }

    public function getRemainingMediawikiToConvert()
    {
        $sql = "SELECT groups.* ".$this->getRemainingMediawikiToConvertQuery();
        return $this->retrieve($sql);
    }

    public function countRemainingMediawikiToConvert()
    {
        $sql = "SELECT count(1) as nb ".$this->getRemainingMediawikiToConvertQuery();
        $row = $this->retrieveFirstRow($sql);
        return $row['nb'];
    }

    private function getRemainingMediawikiToConvertQuery()
    {
        return "FROM plugin_mediawiki_version version
                  JOIN groups ON (groups.group_id = version.project_id)
                  LEFT JOIN plugin_mediawiki_site_restricted_features restricted USING (project_id)
                WHERE groups.status in ('A', 's')
                AND version.mw_version = '1.20'
                AND restricted.project_id IS NULL";
    }
}
