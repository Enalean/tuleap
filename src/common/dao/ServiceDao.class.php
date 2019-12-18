<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *  Data Access Object for Service
 */
class ServiceDao extends DataAccessObject // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
    * Return active projects that use a specific service
    * WARNING: this returns all fields of all projects (might be big)
    * @return DataAccessResult
    */
    public function searchActiveUnixGroupByUsedService($service_short_name)
    {
        $sql = sprintf(
            "SELECT * FROM groups, service
                WHERE groups.group_id=service.group_id AND service.short_name=%s AND service.is_used='1' AND groups.status='A'",
            $this->da->quoteSmart($service_short_name)
        );
        return $this->retrieve($sql);
    }

    public function searchByProjectIdAndShortNames($project_id, $allowed_shortnames)
    {
        $project_id         = $this->da->escapeInt($project_id);
        $allowed_shortnames = $this->da->quoteSmartImplode(',', $allowed_shortnames);

        $sql = "SELECT *
                FROM service
                WHERE group_id = $project_id
                    AND short_name IN ($allowed_shortnames)
                ORDER BY rank";

        return $this->retrieve($sql);
    }

    public function isServiceAvailableAtSiteLevelByShortName($name)
    {
        return $this->isServiceActiveInProjectByShortName(100, $name);
    }

    public function isServiceActiveInProjectByShortName($project_id, $name)
    {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($name);

        $sql = "SELECT NULL
                 FROM service
                 WHERE group_id = $project_id
                     AND is_active = 1
                     AND short_name = $name
                 LIMIT 1";
        $dar = $this->retrieve($sql);
        return $dar->rowCount() === 1;
    }

    public function updateServiceUsageByShortName($project_id, $short_name, $is_used)
    {
        $project_id = $this->da->escapeInt($project_id);
        $short_name = $this->da->quoteSmart($short_name);
        $is_used    = $this->da->escapeInt($is_used);

        $sql = "UPDATE service
                SET is_used = $is_used
                WHERE short_name = $short_name
                AND group_id = $project_id";
        return $this->update($sql);
    }

    public function updateServiceUsageByServiceID($project_id, $service_id, $is_used)
    {
        $project_id = $this->da->escapeInt($project_id);
        $service_id = $this->da->escapeInt($service_id);
        $is_used    = $this->da->escapeInt($is_used);

        $sql = "UPDATE service
                SET is_used = $is_used
                WHERE service_id = $service_id
                AND group_id = $project_id";
        $this->update($sql);
    }

    public function delete($project_id, $id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $id         = $this->da->escapeInt($id);

        $sql = "DELETE FROM service
                WHERE group_id = $project_id AND service_id = $id";

        return $this->update($sql) && $this->da->affectedRows() > 0;
    }

    public function deleteFromAllProjects($short_name)
    {
        $short_name = $this->da->quoteSmart($short_name);

        $sql = "DELETE FROM service WHERE short_name = $short_name";

        return $this->update($sql) && $this->da->affectedRows() > 0;
    }

    public function create(
        $project_id,
        $label,
        $icon_name,
        $description,
        $short_name,
        $link,
        $is_active,
        $is_used,
        $scope,
        $rank,
        $is_in_new_tab
    ) {
        $project_id    = $this->da->escapeInt($project_id);
        $label         = $this->da->quoteSmart($label);
        $icon_name     = $this->da->quoteSmart($icon_name);
        $description   = $this->da->quoteSmart($description);
        $short_name    = $this->da->quoteSmart($short_name);
        $link          = $this->da->quoteSmart($link);
        $scope         = $this->da->quoteSmart($scope);
        $rank          = $this->da->escapeInt($rank);
        $is_active     = $is_active ? 1 : 0;
        $is_used       = $is_used ? 1 : 0;
        $is_in_iframe  = 0;
        $is_in_new_tab = $is_in_new_tab ? 1 : 0;

        $sql = "INSERT INTO service (
                     group_id,
                     label,
                     description,
                     short_name,
                     link,
                     is_active,
                     is_used,
                     scope,
                     rank,
                     is_in_iframe,
                     is_in_new_tab,
                     icon
                 ) VALUES (
                       $project_id,
                       $label,
                       $description,
                       $short_name,
                       $link,
                       $is_active,
                       $is_used,
                       $scope,
                       $rank,
                       $is_in_iframe,
                       $is_in_new_tab,
                       $icon_name
                   )";

        return $this->update($sql) && $this->da->affectedRows() > 0;
    }

    public function saveBasicInformation(
        $service_id,
        $label,
        $icon_name,
        $description,
        $link,
        $rank,
        $is_in_iframe,
        $is_in_new_tab
    ) {
        $service_id   = $this->da->escapeInt($service_id);
        $label        = $this->da->quoteSmart($label);
        $icon_name    = $this->da->quoteSmart($icon_name);
        $description  = $this->da->quoteSmart($description);
        $link         = $this->da->quoteSmart($link);
        $rank         = $this->da->escapeInt($rank);
        $is_in_iframe = $is_in_iframe ? 1 : 0;
        $is_in_new_tab = $is_in_new_tab ? 1 : 0;

        $sql = "UPDATE service
                SET label = $label,
                    icon = $icon_name,
                    description = $description,
                    link = $link,
                    rank = $rank,
                    is_in_iframe = $is_in_iframe,
                    is_in_new_tab = $is_in_new_tab
                WHERE service_id = $service_id";

        return $this->update($sql) && $this->da->affectedRows() > 0;
    }

    public function saveIsActiveAndScope($service_id, $is_active, $scope)
    {
        $service_id = $this->da->escapeInt($service_id);
        $scope      = $this->da->quoteSmart($scope);
        $is_active  = $is_active ? 1 : 0;

        $sql = "UPDATE service
                SET scope = $scope,
                    is_active = $is_active
                WHERE service_id = $service_id";

        $this->update($sql);
    }

    public function searchById(int $id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT * FROM service WHERE service_id = $id";

        return $this->retrieve($sql);
    }

    public function getServiceInfoQueryForNewProject(array $legacy, int $template_id)
    {
        $template_id      = $this->da->escapeInt($template_id);
        $additional_where = '';

        foreach ($legacy as $service_shortname => $legacy_service_usage) {
            if (! $legacy_service_usage) {
                $service_shortname =  $this->da->quoteSmart($service_shortname);
                $additional_where  .= " AND short_name <> $service_shortname";
            }
        }

        $sql = "SELECT * FROM service WHERE group_id=$template_id AND is_active=1 $additional_where";

        return $this->retrieve($sql);
    }
}
