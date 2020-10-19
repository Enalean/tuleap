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

namespace Tuleap\ScaledAgile\Program\Milestone\CreationCheck;

use Psr\Log\LoggerInterface;
use Tuleap\ScaledAgile\Program\Milestone\SynchronizedFieldCollection;
use Tuleap\ScaledAgile\Program\Milestone\TeamMilestoneTrackerCollection;

class RequiredFieldChecker
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
        TeamMilestoneTrackerCollection $team_milestones,
        SynchronizedFieldCollection $field_collection
    ): bool {
        foreach ($team_milestones->getMilestoneTrackers() as $team_milestone_tracker) {
            foreach ($team_milestone_tracker->getFormElementFields() as $field) {
                if ($field->isRequired() && ! $field_collection->isFieldSynchronized($field)) {
                    $this->logger->debug(
                        sprintf(
                            "Field #%d (%s) of tracker #%d is required but cannot be synchronized",
                            $field->getId(),
                            $field->getLabel(),
                            $team_milestone_tracker->getId()
                        )
                    );
                    return false;
                }
            }
        }

        return true;
    }
}
