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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use Psr\Log\LoggerInterface;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollection;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldCollection;

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

    public function areRequiredFieldsOfContributorTrackersLimitedToTheSynchronizedFields(
        MilestoneTrackerCollection $tracker_collection,
        SynchronizedFieldCollection $field_collection
    ): bool {
        foreach ($tracker_collection->getContributorMilestoneTrackers() as $contributor_milestone_tracker) {
            foreach ($contributor_milestone_tracker->getFormElementFields() as $field) {
                if ($field->isRequired() && ! $field_collection->isFieldSynchronized($field)) {
                    $this->logger->debug(
                        sprintf(
                            "Field #%d (%s) of tracker #%d is required but cannot be synchronized",
                            $field->getId(),
                            $field->getLabel(),
                            $contributor_milestone_tracker->getId()
                        )
                    );
                    return false;
                }
            }
        }

        return true;
    }
}
