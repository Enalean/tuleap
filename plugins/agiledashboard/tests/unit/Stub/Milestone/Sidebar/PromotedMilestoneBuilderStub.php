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
use Tuleap\AgileDashboard\Milestone\Sidebar\BuildPromotedMilestone;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * @psalm-immutable
 */
final class PromotedMilestoneBuilderStub implements BuildPromotedMilestone
{
    private int $nb_called = 0;
    /**
     * @param Option<Planning_ArtifactMilestone>[] $milestones
     */
    private function __construct(private array $milestones)
    {
    }

    public static function buildWithNothing(): self
    {
        return new self([Option::nothing(Planning_ArtifactMilestone::class)]);
    }

    public static function buildWithPlanningArtifactMilestone(Planning_ArtifactMilestone ...$milestones): self
    {
        $milestone_list = [];
        foreach ($milestones as $artifact) {
            $milestone_list[] = Option::fromValue($artifact);
        }
        return new self($milestone_list);
    }

    public function build(Artifact $milestone_artifact, PFUser $user, \Project $project): Option
    {
        $this->nb_called++;

        if (count($this->milestones) > 0) {
            return array_shift($this->milestones);
        }

        throw new \LogicException('No promoted milestone configured');
    }

    public function getNbCalled(): int
    {
        return $this->nb_called;
    }
}
