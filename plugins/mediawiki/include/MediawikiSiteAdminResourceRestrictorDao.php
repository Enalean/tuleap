<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

class MediawikiSiteAdminResourceRestrictorDao extends RestrictedResourceDao
{

    public function getResourceAllowedProjectsTableName()
    {
        return 'plugin_mediawiki_site_restricted_features';
    }

    public function getResourceFieldName()
    {
        return 'feature';
    }

    public function isResourceRestricted($resource_id)
    {
        return true;
    }

    public function searchAllowedProjectsOnResource($resource_id)
    {
        $resource_id = $this->da->escapeInt($resource_id);
        $sql = "SELECT groups.*
                FROM groups
                  JOIN plugin_mediawiki_site_restricted_features mwf ON (mwf.project_id = groups.group_id)
                WHERE mwf.feature = $resource_id
                  AND status IN ('A', 's')";
        return $this->retrieve($sql);
    }

    public function setResourceRestricted($resource_id)
    {
        return false;
    }

    public function unsetResourceRestricted($resource_id)
    {
        return false;
    }
}
