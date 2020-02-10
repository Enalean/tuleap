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

namespace Tuleap\Timetracking\REST\v1;

use Tracker_ArtifactFactory;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\REST\v1\Exception\ArtifactDoesNotExistException;
use Tuleap\Timetracking\REST\v1\Exception\ArtifactIDMissingException;
use Tuleap\Timetracking\REST\v1\Exception\InvalidArgumentException;
use Tuleap\Timetracking\REST\v1\Exception\NoTimetrackingForTrackerException;
use Tuleap\Timetracking\REST\v1\Exception\UserCannotSeeTrackedTimeException;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimeRetriever;

class ArtifactTimeRetriever
{

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var ProjectStatusVerificator
     */
    private $project_status_verificator;
    /**
     * @var TimetrackingEnabler
     */
    private $timetracking_enabler;
    /**
     * @var PermissionsRetriever
     */
    private $permissions_retriever;
    /**
     * @var TimeRetriever
     */
    private $time_retriever;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        ProjectStatusVerificator $project_status_verificator,
        TimetrackingEnabler $timetracking_enabler,
        PermissionsRetriever $permissions_retriever,
        TimeRetriever $time_retriever
    ) {
        $this->artifact_factory           = $artifact_factory;
        $this->project_status_verificator = $project_status_verificator;
        $this->timetracking_enabler       = $timetracking_enabler;
        $this->permissions_retriever      = $permissions_retriever;
        $this->time_retriever             = $time_retriever;
    }

    public static function build()
    {
        $admin_dao             = new AdminDao();
        $permissions_retriever = new PermissionsRetriever(
            new TimetrackingUgroupRetriever(
                new TimetrackingUgroupDao()
            )
        );
        return new self(
            Tracker_ArtifactFactory::instance(),
            ProjectStatusVerificator::build(),
            new TimetrackingEnabler($admin_dao),
            $permissions_retriever,
            new TimeRetriever(
                new TimeDao(),
                $permissions_retriever,
                $admin_dao,
                \ProjectManager::instance()
            )
        );
    }

    /**
     * @param string $query
     *
     * @throws InvalidArgumentException
     * @throws \Luracast\Restler\RestException
     */
    public function getArtifactTime(\PFUser $user, $query)
    {
        $query = \json_decode($query, true);
        if (! isset($query['artifact_id'])) {
            throw new ArtifactIDMissingException();
        }

        $artifact_id = (int) $query['artifact_id'];

        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $artifact_id);
        if (! $artifact) {
            throw new ArtifactDoesNotExistException();
        }

        $this->project_status_verificator->checkProjectStatusAllowsAllUsersToAccessIt($artifact->getTracker()->getProject());

        if (! $this->timetracking_enabler->isTimetrackingEnabledForTracker($artifact->getTracker())) {
            throw new NoTimetrackingForTrackerException();
        }

        if (! $this->permissions_retriever->userCanSeeAggregatedTimesInTracker($user, $artifact->getTracker())) {
            throw new UserCannotSeeTrackedTimeException();
        }

        $times = [];
        foreach ($this->time_retriever->getTimesForUser($user, $artifact) as $time) {
            $times[] = ArtifactTimeRepresentation::build($time);
        }
        return $times;
    }
}
