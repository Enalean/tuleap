<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Sidebar;

use Planning_ArtifactMilestone;
use Tuleap\AgileDashboard\AgileDashboard\Milestone\Sidebar\PromotedMilestoneWithItsSubmilestones;
use Tuleap\Option\Option;

final class PromotedMilestoneList
{
    private const MAX_ITEMS = 5;

    /**
     * @var array<int, Planning_ArtifactMilestone>
     */
    private array $milestone_list = [];

    /**
     * @var array<int, Planning_ArtifactMilestone[]>
     */
    private array $sub_milestone_list = [];

    public function addMilestone(Planning_ArtifactMilestone $milestone): void
    {
        if ($this->isListSizeLimitReached()) {
            return;
        }

        $this->milestone_list[$milestone->getArtifactId()] = $milestone;
    }

    public function addSubMilestone(Planning_ArtifactMilestone $parent_milestone, Planning_ArtifactMilestone $sub_milestone): void
    {
        if ($this->isListSizeLimitReached()) {
            return;
        }

        $parent_milestone_id = $parent_milestone->getArtifactId();

        if (! isset($this->sub_milestone_list[$parent_milestone_id])) {
            $this->sub_milestone_list[$parent_milestone_id] = [];
        }

        $this->sub_milestone_list[$parent_milestone_id][] = $sub_milestone;
    }

    /**
     * @return PromotedMilestoneWithItsSubmilestones[]
     */
    public function getMilestoneList(): array
    {
        return array_values(
            array_map(
                fn (Planning_ArtifactMilestone $milestone) => new PromotedMilestoneWithItsSubmilestones(
                    $milestone,
                    ...($this->sub_milestone_list[$milestone->getArtifactId()] ?? [])
                ),
                $this->milestone_list,
            ),
        );
    }

    /**
     * @return Option<Planning_ArtifactMilestone>
     */
    public function getMilestone(int $milestone_id): Option
    {
        if ($this->containsMilestone($milestone_id)) {
            return Option::fromValue($this->milestone_list[$milestone_id]);
        }

        return Option::nothing(Planning_ArtifactMilestone::class);
    }

    public function containsMilestone(int $milestone_id): bool
    {
        return isset($this->milestone_list[$milestone_id]);
    }

    private function getTotalSize(): int
    {
        return count($this->milestone_list)
            + count($this->sub_milestone_list, COUNT_RECURSIVE)
            - count($this->sub_milestone_list); // so that we don't double count a milestone if it has submilestones
    }

    public function isListSizeLimitReached(): bool
    {
        return $this->getTotalSize() >= self::MAX_ITEMS;
    }
}
