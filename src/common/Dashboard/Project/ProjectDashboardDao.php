<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Project;

use DataAccessObject;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;

class ProjectDashboardDao extends DataAccessObject
{

    /**
     * @var DashboardWidgetDao
     */
    private $widget_dao;

    public function __construct(DashboardWidgetDao $widget_dao)
    {
        parent::__construct();
        $this->enableExceptionsOnError();
        $this->widget_dao = $widget_dao;
    }

    public function searchAllProjectDashboards($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT *
                FROM project_dashboards
                WHERE project_id=$project_id";

        return $this->retrieve($sql);
    }

    /**
     * @param $project_id
     * @param $name
     * @return int
     */
    public function save($project_id, $name)
    {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($name);

        $sql = "INSERT INTO project_dashboards(project_id, name)
                VALUES ($project_id, $name)";

        return $this->updateAndGetLastId($sql);
    }

    /**
     * @param $dashboard_id
     * @return \DataAccessResult|false
     */
    public function searchById($dashboard_id)
    {
        $dashboard_id = $this->da->escapeInt($dashboard_id);

        $sql = "SELECT *
                FROM project_dashboards
                WHERE id=$dashboard_id";

        return $this->retrieve($sql);
    }

    /**
     * @param $project_id
     * @param $name
     * @return \DataAccessResult|false
     */
    public function searchByProjectIdAndName($project_id, $name)
    {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($name);

        $sql = "SELECT *
                FROM project_dashboards
                WHERE project_id=$project_id AND name=$name";

        return $this->retrieve($sql);
    }

    /**
     * @param $id
     * @param $name
     * @return bool
     */
    public function edit($id, $name)
    {
        $id   = $this->da->escapeInt($id);
        $name = $this->da->quoteSmart($name);

        $sql = "UPDATE
                project_dashboards
                SET name = $name
                WHERE id = $id";

        return $this->update($sql);
    }

    /**
     * @param $project_id
     * @param $dashboard_id
     */
    public function delete($project_id, $dashboard_id)
    {
        $this->da->startTransaction();
        try {
            $this->widget_dao->deleteAllWidgetsInProjectDashboardInsideTransaction($project_id, $dashboard_id);

            $project_id   = $this->da->escapeInt($project_id);
            $dashboard_id = $this->da->escapeInt($dashboard_id);

            $sql = "DELETE FROM project_dashboards WHERE project_id = $project_id AND id = $dashboard_id";

            $this->update($sql);
            $this->da->commit();
        } catch (\Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    public function duplicateDashboard($template_id, $new_project_id, $template_dashboard_id)
    {
        $template_id           = $this->da->escapeInt($template_id);
        $new_project_id        = $this->da->escapeInt($new_project_id);
        $template_dashboard_id = $this->da->escapeInt($template_dashboard_id);

        $sql = "INSERT INTO project_dashboards (project_id, name)
                SELECT $new_project_id, name
                FROM project_dashboards
                WHERE project_id = $template_id
                  AND id = $template_dashboard_id";

        return $this->updateAndGetLastId($sql);
    }
}
