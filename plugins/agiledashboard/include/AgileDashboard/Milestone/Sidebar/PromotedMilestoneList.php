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
use Tuleap\Option\Option;

final class PromotedMilestoneList
{
    public const MAX_ITEMS = 5;

    /**
     * @var PromotedMilestone[]
     */
    private array $milestone_list = [];
    private int $list_size        = 0;

    /**
     * @param Option<Planning_ArtifactMilestone> $promoted_milestone
     */
    public function addMilestone(Option $promoted_milestone): void
    {
        if ($this->list_size === self::MAX_ITEMS) {
            return;
        }
        $promoted_milestone->apply(function ($milestone) {
            if (! $this->containsMilestone($milestone->getArtifactId())) {
                $this->milestone_list[$milestone->getArtifactId()] = new PromotedMilestone($milestone);
                $this->list_size++;
            }
        });
    }

    public function containsMilestone(int $milestone_id): bool
    {
        return isset($this->milestone_list[$milestone_id]);
    }

    /**
     * @param Option<Planning_ArtifactMilestone> $sub_milestone
     */
    public function addSubMilestoneIntoMilestone(int $milestone_id, Option $sub_milestone): void
    {
        if ($this->list_size === self::MAX_ITEMS) {
            return;
        }

        if (isset($this->milestone_list[$milestone_id])) {
            $this->milestone_list[$milestone_id]->addPromotedSubMilestone($sub_milestone);
            $this->list_size++;
        }
    }

    /**
     * @return PromotedMilestone[]
     */
    public function getMilestoneList(): array
    {
        return $this->milestone_list;
    }

    public function getMilestone(int $milestone_id): ?PromotedMilestone
    {
        if ($this->containsMilestone($milestone_id)) {
            return $this->milestone_list[$milestone_id];
        }

        return null;
    }

    public function getListSize(): int
    {
        return $this->list_size;
    }
}
