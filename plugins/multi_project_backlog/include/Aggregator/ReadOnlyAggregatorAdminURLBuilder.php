<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\MultiProjectBacklog\Aggregator;

use Planning;
use ProjectManager;

class ReadOnlyAggregatorAdminURLBuilder
{
    /**
     * @var AggregatorDao
     */
    private $aggregator_dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(AggregatorDao $aggregator_dao, ProjectManager $project_manager)
    {
        $this->aggregator_dao  = $aggregator_dao;
        $this->project_manager = $project_manager;
    }

    public function buildURL(Planning $planning, ?Planning $root_planning): ?string
    {
        if ($root_planning === null) {
            return null;
        }

        $project_id = (int) $planning->getGroupId();

        if (! $this->aggregator_dao->isProjectAnAggregatorProject($project_id)) {
            return null;
        }

        $planning_id      = (int) $planning->getId();
        $root_planning_id = (int) $root_planning->getId();

        if ($planning_id !== $root_planning_id) {
            return null;
        }

        $project = $this->project_manager->getProject($project_id);

        return '/project/' . urlencode($project->getUnixName()) . '/backlog/admin/' . urlencode((string) $planning_id);
    }
}
