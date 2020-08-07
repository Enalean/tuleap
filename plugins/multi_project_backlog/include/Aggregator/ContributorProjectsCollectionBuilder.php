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

use Project;
use ProjectManager;

class ContributorProjectsCollectionBuilder
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
        $this->aggregator_dao = $aggregator_dao;
        $this->project_manager = $project_manager;
    }

    public function getContributorProjectForAGivenAggregatorProject(Project $project): ContributorProjectsCollection
    {
        $aggregator_project_id = (int) $project->getID();
        $contributor_projects  = [];
        foreach ($this->aggregator_dao->getContributorProjectIdsForGivenAggregatorProject($aggregator_project_id) as $row) {
            $contributor_projects[] = $this->project_manager->getProject($row['contributor_project_id']);
        }

        return new ContributorProjectsCollection($contributor_projects);
    }
}
