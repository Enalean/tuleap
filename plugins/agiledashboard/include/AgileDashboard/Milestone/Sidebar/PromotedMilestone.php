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

final class PromotedMilestone
{
    /**
     * @var Planning_ArtifactMilestone[]
     */
    private array $sub_milestone_list = [];

    /**
     * @psalm-internal Tuleap\AgileDashboard\Milestone\Sidebar
     * @internal Promoted milestone should only exist inside a ListContext @see Tuleap\AgileDashboard\Milestone\Sidebar\PromotedMilestoneList
     */
    public function __construct(private readonly Planning_ArtifactMilestone $milestone)
    {
    }

    /**
     * @param Option<Planning_ArtifactMilestone> $promoted_sub_milestone
     */
    public function addPromotedSubMilestone(Option $promoted_sub_milestone): void
    {
        $promoted_sub_milestone->apply(function ($sub_milestone) {
            $this->sub_milestone_list[] = $sub_milestone;
        });
    }

    /**
     * @return Planning_ArtifactMilestone[]
     */
    public function getSubMilestoneList(): array
    {
        return $this->sub_milestone_list;
    }

    public function getMilestone(): Planning_ArtifactMilestone
    {
        return $this->milestone;
    }
}
