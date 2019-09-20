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

abstract class RestrictedResourceDao extends DataAccessObject
{

    abstract public function getResourceAllowedProjectsTableName();

    abstract public function getResourceFieldName();


    abstract public function isResourceRestricted($resource_id);

    abstract public function setResourceRestricted($resource_id);

    abstract public function unsetResourceRestricted($resource_id);

    abstract public function searchAllowedProjectsOnResource($resource_id);


    public function allowProjectOnResource($resource_id, $project_id)
    {
        $resource_id = $this->da->escapeInt($resource_id);
        $project_id  = $this->da->escapeInt($project_id);

        $sql = "REPLACE INTO " . $this->getResourceAllowedProjectsTableName() . " (" . $this->getResourceFieldName() . ", project_id) VALUES ($resource_id, $project_id)";

        return $this->update($sql);
    }

    public function revokeAllProjectsFromResource($resource_id)
    {
        $resource_id = $this->da->escapeInt($resource_id);

        $sql = "DELETE FROM " . $this->getResourceAllowedProjectsTableName() . "
                WHERE " . $this->getResourceFieldName() . " = $resource_id";

        return $this->update($sql);
    }

    public function revokeProjectsFromResource($resource_id, $project_ids)
    {
        $resource_id = $this->da->escapeInt($resource_id);
        $project_ids = $this->da->escapeIntImplode($project_ids);

        $sql = "DELETE FROM " . $this->getResourceAllowedProjectsTableName() . "
                WHERE " . $this->getResourceFieldName() . " = $resource_id
                AND project_id IN ($project_ids)";

        return $this->update($sql);
    }
}
