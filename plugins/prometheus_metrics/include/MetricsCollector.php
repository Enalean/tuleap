<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\PrometheusMetrics;

use SystemEvent;
use Project;
use Tuleap\Admin\Homepage\NbUsersByStatusBuilder;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Instrument\Prometheus\CollectTuleapComputedMetrics;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Queue\Worker;

class MetricsCollector
{
    private const PROJECT_STATUS = [
        Project::STATUS_ACTIVE  => 'active',
        Project::STATUS_PENDING => 'pending',
        Project::STATUS_DELETED => 'deleted',
    ];

    /**
     * @var Prometheus
     */
    private $prometheus;

    /**
     * @var MetricsCollectorDao
     */
    private $dao;
    /**
     * @var NbUsersByStatusBuilder
     */
    private $nb_user_builder;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var VersionPresenter
     */
    private $version_presenter;
    /**
     * @var \Redis|null
     */
    private $redis;

    public function __construct(
        Prometheus $prometheus,
        MetricsCollectorDao $dao,
        NbUsersByStatusBuilder $nb_user_builder,
        \EventManager $event_manager,
        VersionPresenter $version_presenter,
        ?\Redis $redis
    ) {
        $this->prometheus        = $prometheus;
        $this->dao               = $dao;
        $this->nb_user_builder   = $nb_user_builder;
        $this->event_manager     = $event_manager;
        $this->version_presenter = $version_presenter;
        $this->redis             = $redis;
    }

    public function collect(): void
    {
        $this->setUsersByStatus();
        $this->setProjectsByStatus();
        $this->setWorkerStatus();
        $this->setSystemEventsStatus();
        $this->setBuildInfo();
        $this->event_manager->processEvent(new CollectTuleapComputedMetrics($this->prometheus));
    }

    private function setUsersByStatus(): void
    {
        $nb_users_by_status = $this->nb_user_builder->getNbUsersByStatusBuilder();

        $this->setUsersTotal('pending', (float) $nb_users_by_status->getNbPending());
        $this->setUsersTotal('active', (float) $nb_users_by_status->getNbActive());
        $this->setUsersTotal('validated', (float) $nb_users_by_status->getNbAllValidated());
        $this->setUsersTotal('restricted', (float) $nb_users_by_status->getNbRestricted());
        $this->setUsersTotal('suspended', (float) $nb_users_by_status->getNbSuspended());
        $this->setUsersTotal('deleted', (float) $nb_users_by_status->getNbDeleted());
    }

    private function setUsersTotal(string $type, float $value): void
    {
        $this->prometheus->gaugeSet('users_total', 'Total number of users by type', $value, ['type' => $type]);
    }

    private function setProjectsByStatus(): void
    {
        foreach ($this->dao->getProjectsByStatus() as $row) {
            $this->setProjectsTotal(self::PROJECT_STATUS[$row['status']], $row['nb']);
        }
    }

    private function setProjectsTotal($type, $value): void
    {
        $this->prometheus->gaugeSet('projects_total', 'Total number of projects by type', $value, ['type' => $type]);
    }

    private function setWorkerStatus(): void
    {
        if ($this->redis !== null) {
            $nb_events = $this->redis->lLen(Worker::EVENT_QUEUE_NAME);
            $this->prometheus->gaugeSet('worker_events', 'Total number of worker events', $nb_events, ['queue' => Worker::EVENT_QUEUE_NAME]);
        }
    }

    private function setSystemEventsStatus(): void
    {
        $all_status = [];
        foreach (SystemEvent::ALL_STATUS as $status) {
            $all_status[$status] = 0;
        }
        foreach ($this->dao->getNewSystemEventsCount() as $row) {
            $all_status[$row['status']] = (int) $row['nb'];
        }
        foreach ($all_status as $status => $count) {
            $this->prometheus->gaugeSet('system_events_count', 'Actual number (as in the database) of system_events per type', $count, ['status' => $status]);
        }
    }

    private function setBuildInfo(): void
    {
        $this->prometheus->gaugeSet(
            'build_info',
            "A metric with a constant '1' value labelled by flavor and version",
            1,
            [
                'flavor'  => $this->version_presenter->flavor_name,
                'version' => $this->version_presenter->version_number,
            ]
        );
    }
}
