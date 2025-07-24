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
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveMirroredProgramIncrementTrackerStub implements RetrieveMirroredProgramIncrementTracker
{
    private function __construct(private bool $has_no_planning, private array $trackers)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withValidTrackers(TrackerReference $tracker, TrackerReference ...$other_trackers): self
    {
        return new self(false, [$tracker, ...$other_trackers]);
    }

    public static function withNoRootPlanning(): self
    {
        return new self(true, []);
    }

    #[\Override]
    public function retrieveRootPlanningMilestoneTracker(
        ProjectReference $project,
        UserIdentifier $user_identifier,
        ?ConfigurationErrorsCollector $errors_collector,
    ): ?TrackerReference {
        if ($this->has_no_planning) {
            $errors_collector?->addTeamMilestonePlanningNotFoundOrNotAccessible($project);
            return null;
        }
        if (count($this->trackers) > 0) {
            return array_shift($this->trackers);
        }

        return null;
    }
}
