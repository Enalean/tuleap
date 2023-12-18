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

namespace Tuleap\AgileDashboard\Stub\Milestone\Sidebar;

use PFUser;
use Planning_ArtifactMilestone;
use Planning_VirtualTopMilestone;
use Tuleap\AgileDashboard\Milestone\Sidebar\BuildPromotedMilestoneList;
use Tuleap\AgileDashboard\Milestone\Sidebar\PromotedMilestoneList;
use Tuleap\Option\Option;

/**
 * @psalm-immutable
 */
final class BuildPromotedMilestoneListStub implements BuildPromotedMilestoneList
{
    private function __construct(private readonly PromotedMilestoneList $promoted_milestone_list)
    {
    }

    public static function buildFromEmpty(): self
    {
        $list = new PromotedMilestoneList();
        return new self($list);
    }

    public static function buildWithValues(Planning_ArtifactMilestone $milestone, Planning_ArtifactMilestone $sub_milestone): self
    {
        $list = new PromotedMilestoneList();
        $list->addMilestone(Option::fromValue($milestone));
        $list->addSubMilestoneIntoMilestone($milestone->getArtifactId(), Option::fromValue($sub_milestone));
        return new self($list);
    }

    public function buildPromotedMilestoneList(PFUser $user, Planning_VirtualTopMilestone $virtual_top_milestone): PromotedMilestoneList
    {
        return $this->promoted_milestone_list;
    }
}
