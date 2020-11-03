<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\CreationCheck;

use Psr\Log\LoggerInterface;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackersCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team\ProgramIncrementsTrackerCollection;

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
        ProgramIncrementsTrackerCollection $program_increment_trackers,
        SynchronizedFieldDataFromProgramAndTeamTrackersCollection $field_collection
    ): bool {
        foreach ($program_increment_trackers->getProgramIncrementTrackers() as $program_increment_tracker) {
            foreach ($program_increment_tracker->getFullTracker()->getFormElementFields() as $field) {
                if ($field->isRequired() && ! $field_collection->isFieldSynchronized($field)) {
                    $this->logger->debug(
                        sprintf(
                            "Field #%d (%s) of tracker #%d is required but cannot be synchronized",
                            $field->getId(),
                            $field->getLabel(),
                            $program_increment_tracker->getTrackerId()
                        )
                    );
                    return false;
                }
            }
        }

        return true;
    }
}
