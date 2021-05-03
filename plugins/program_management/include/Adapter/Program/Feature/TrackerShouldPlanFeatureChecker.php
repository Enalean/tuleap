<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanProgramIncrementConfigurationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\PlanCheckException;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;

class TrackerShouldPlanFeatureChecker
{
    /**
     * @var PlanProgramIncrementConfigurationBuilder
     */
    private $configuration_builder;

    public function __construct(PlanProgramIncrementConfigurationBuilder $configuration_builder)
    {
        $this->configuration_builder = $configuration_builder;
    }

    public function checkTrackerCanPlanFeature(ArtifactUpdated $event): bool
    {
        try {
            $program_tracker = $this->configuration_builder->buildTrackerProgramIncrementFromProjectId(
                (int) $event->getProject()->getGroupId(),
                $event->getUser()
            );
        } catch (PlanTrackerException | ProgramTrackerException | PlanCheckException $e) {
            return false;
        }

        return ! ($program_tracker->getTrackerId() !== $event->getArtifact()->getTrackerId());
    }
}
