<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeaturePlanChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Feature\PlanUserStoryInOneMirror;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class PlanUserStoryInOneMirrorStub implements PlanUserStoryInOneMirror
{
    private function __construct(
        private array $featured_planned_in_mirrors,
        private array $featured_unplanned_from_mirrors,
    ) {
    }

    public static function build(): self
    {
        return new self([], []);
    }

    #[\Override]
    public function planInOneMirror(ProgramIncrementIdentifier $program_increment, MirroredProgramIncrementIdentifier $mirrored_program_increment, FeaturePlanChange $feature_plan_change, UserIdentifier $user_identifier,): void
    {
        $this->featured_planned_in_mirrors[$mirrored_program_increment->getId()]     = $feature_plan_change->user_stories;
        $this->featured_unplanned_from_mirrors[$mirrored_program_increment->getId()] = $feature_plan_change->user_stories_to_remove;
    }

    /**
     * @return int[]
     */
    public function getFeaturedPlannedInMirrors(int $mirror_id): array
    {
        if (! isset($this->featured_planned_in_mirrors[$mirror_id])) {
            return [];
        }

        return array_column($this->featured_planned_in_mirrors[$mirror_id], 'id');
    }

    /**
     * @return int[]
     */
    public function getFeaturedUnplannedFromMirrors(int $mirror_id): array
    {
        if (! isset($this->featured_unplanned_from_mirrors[$mirror_id])) {
            return [];
        }

        return array_column($this->featured_unplanned_from_mirrors[$mirror_id], 'id');
    }
}
