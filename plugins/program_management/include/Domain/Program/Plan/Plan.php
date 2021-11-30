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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramUserGroupCollection;

/**
 * @psalm-immutable
 */
final class Plan
{
    /**
     * @var ProgramIncrementTracker
     */
    private $program_increment_tracker;
    /**
     * @var ProgramPlannableTracker[]
     */
    private $plannable_trackers;
    private ProgramUserGroupCollection $can_prioritize;
    /**
     * @var string|null
     */
    private $custom_label;
    /**
     * @var string|null
     */
    private $custom_sub_label;
    /**
     * @var ?IterationTracker
     */
    private $iteration_tracker;
    /**
     * @var int
     */
    private $project_id;

    /**
     * @param ProgramPlannableTracker[] $plannable_trackers
     */
    public function __construct(
        ProgramIncrementTracker $program_increment_tracker,
        int $project_id,
        array $plannable_trackers,
        ProgramUserGroupCollection $can_prioritize,
        ?string $custom_label,
        ?string $custom_sub_label,
        ?IterationTracker $iteration_tracker,
    ) {
        $this->program_increment_tracker = $program_increment_tracker;
        $this->project_id                = $project_id;
        $this->plannable_trackers        = $plannable_trackers;
        $this->can_prioritize            = $can_prioritize;
        $this->custom_label              = $custom_label;
        $this->custom_sub_label          = $custom_sub_label;
        $this->iteration_tracker         = $iteration_tracker;
    }

    public function getProgramIncrementTracker(): ProgramIncrementTracker
    {
        return $this->program_increment_tracker;
    }

    /**
     * @return int[]
     */
    public function getPlannableTrackerIds(): array
    {
        return array_map(
            static function (ProgramPlannableTracker $tracker) {
                return $tracker->getId();
            },
            $this->plannable_trackers
        );
    }

    /**
     * @return non-empty-list<ProgramUserGroup>
     */
    public function getCanPrioritize(): array
    {
        return $this->can_prioritize->getUserGroups();
    }

    public function getCustomLabel(): ?string
    {
        return $this->custom_label;
    }

    public function getCustomSubLabel(): ?string
    {
        return $this->custom_sub_label;
    }

    public function getIterationTracker(): ?IterationTracker
    {
        return $this->iteration_tracker;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }
}
