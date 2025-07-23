<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\PlanningHasNoMilestoneTrackerException;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredIterationTracker;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveMirroredIterationTrackerStub implements RetrieveMirroredIterationTracker
{
    private function __construct(
        private bool $has_no_root_planning,
        private bool $has_no_second_planning,
        private bool $has_broken_planning,
        private array $trackers,
    ) {
    }

    public static function withValidTrackers(TrackerReference ...$trackers): self
    {
        return new self(false, false, false, $trackers);
    }

    public static function withNoVisibleRootPlanning(): self
    {
        return new self(true, false, false, []);
    }

    public static function withNoVisibleSecondPlanning(): self
    {
        return new self(false, true, false, []);
    }

    public static function withBrokenPlanning(): self
    {
        return new self(false, false, true, []);
    }

    #[\Override]
    public function retrieveSecondPlanningMilestoneTracker(
        ProjectReference $project,
        UserIdentifier $user,
        ?ConfigurationErrorsCollector $errors_collector,
    ): ?TrackerReference {
        if ($this->has_no_root_planning) {
            $errors_collector?->addTeamMilestonePlanningNotFoundOrNotAccessible($project);
            return null;
        }
        if ($this->has_no_second_planning) {
            $errors_collector?->addTeamSprintPlanningNotFoundOrNotAccessible($project);
            return null;
        }
        if ($this->has_broken_planning) {
            throw new PlanningHasNoMilestoneTrackerException(128);
        }
        if (count($this->trackers) > 0) {
            return array_shift($this->trackers);
        }

        return null;
    }
}
