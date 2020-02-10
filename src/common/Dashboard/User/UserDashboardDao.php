<?php
/**
 * Copyright (c) Enalean, 2017-2018. All rights reserved
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

namespace Tuleap\Dashboard\User;

use DataAccessObject;
use PFUser;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;

class UserDashboardDao extends DataAccessObject
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

    public function searchAllUserDashboards(PFUser $user)
    {
        $user_id = $this->da->escapeInt($user->getId());

        $sql = "SELECT *
                FROM user_dashboards
                WHERE user_id=$user_id";

        return $this->retrieve($sql);
    }

    /**
     * @param $name
     * @return bool
     */
    public function save(PFUser $user, $name)
    {
        $user_id = $this->da->escapeInt($user->getId());
        $name    = $this->da->quoteSmart($name);

        $sql = "REPLACE INTO user_dashboards(user_id, name)
                VALUES ($user_id, $name)";

        return $this->updateAndGetLastId($sql);
    }

    public function searchByUserIdAndName(PFUser $user, $name)
    {
        $user_id = $this->da->escapeInt($user->getId());
        $name    = $this->da->quoteSmart($name);

        $sql = "SELECT *
                FROM user_dashboards
                WHERE user_id=$user_id AND name=$name";

        return $this->retrieve($sql);
    }

    public function delete($user_id, $dashboard_id)
    {
        $this->da->startTransaction();
        try {
            $this->widget_dao->deleteAllWidgetsInUserDashboardInsideTransaction($user_id, $dashboard_id);

            $user_id      = $this->da->escapeInt($user_id);
            $dashboard_id = $this->da->escapeInt($dashboard_id);

            $sql = "DELETE FROM user_dashboards WHERE user_id = $user_id AND id = $dashboard_id";

            $this->update($sql);
            if ($this->da->affectedRows() === 0) {
                throw new \DataAccessException();
            }
            $this->da->commit();
        } catch (\Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    public function edit($user_id, $id, $name)
    {
        $user_id = $this->da->escapeInt($user_id);
        $id      = $this->da->escapeInt($id);
        $name    = $this->da->quoteSmart($name);

        $sql = "UPDATE
                user_dashboards
                SET name = $name
                WHERE user_id = $user_id AND id = $id";

        $this->update($sql);
        if ($this->da->affectedRows() === 0) {
            throw new \DataAccessException();
        }
    }
}
