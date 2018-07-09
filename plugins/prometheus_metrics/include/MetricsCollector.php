<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\PrometheusMetrics;

use Tuleap\Admin\Homepage\NbUsersByStatusBuilder;
use Tuleap\Admin\Homepage\UserCounterDao;
use Tuleap\Instrument\Prometheus\Prometheus;

class MetricsCollector
{
    /**
     * @var Prometheus
     */
    private $prometheus;
    /**
     * @var MetricsCollectorDao
     */
    private $dao;

    private $project_status = [
        \Project::STATUS_ACTIVE  => 'active',
        \Project::STATUS_PENDING => 'pending',
        \Project::STATUS_DELETED => 'deleted',
    ];
    /**
     * @var NbUsersByStatusBuilder
     */
    private $nb_user_builder;

    public function __construct(Prometheus $prometheus, MetricsCollectorDao $dao, NbUsersByStatusBuilder $nb_user_builder)
    {
        $this->prometheus      = $prometheus;
        $this->dao             = $dao;
        $this->nb_user_builder = $nb_user_builder;
    }

    public static function build(Prometheus $prometheus)
    {
        return new self($prometheus, new MetricsCollectorDao(), new NbUsersByStatusBuilder(new UserCounterDao()));
    }

    public function collect()
    {
        $this->setUsersByStatus();
        $this->setProjectsByStatus();
    }

    private function setUsersByStatus()
    {
        $nb_users_by_status = $this->nb_user_builder->getNbUsersByStatusBuilder();

        $this->setUsersTotal('pending', $nb_users_by_status->getNbPending());
        $this->setUsersTotal('active', $nb_users_by_status->getNbActive());
        $this->setUsersTotal('validated', $nb_users_by_status->getNbAllValidated());
        $this->setUsersTotal('restricted', $nb_users_by_status->getNbRestricted());
        $this->setUsersTotal('suspended', $nb_users_by_status->getNbSuspended());
        $this->setUsersTotal('deleted', $nb_users_by_status->getNbDeleted());
    }

    private function setUsersTotal($type, $value)
    {
        $this->prometheus->gaugeSet('users_total', 'Total number of users by type', $value, ['type' => $type]);
    }

    private function setProjectsByStatus()
    {
        foreach ($this->dao->getProjectsByStatus() as $row) {
            $this->setProjectsTotal($this->project_status[$row['status']], $row['nb']);
        }
    }

    private function setProjectsTotal($type, $value)
    {
        $this->prometheus->gaugeSet('projects_total', 'Total number of projects by type', $value, ['type' => $type]);
    }
}
