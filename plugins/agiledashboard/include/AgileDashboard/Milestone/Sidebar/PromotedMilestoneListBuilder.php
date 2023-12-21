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

use PFUser;
use Planning_ArtifactMilestone;
use Planning_VirtualTopMilestone;
use Tracker_ArtifactFactory;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;

final class PromotedMilestoneListBuilder implements BuildPromotedMilestoneList
{
    public function __construct(
        private readonly Tracker_ArtifactFactory $artifact_factory,
        private readonly BuildPromotedMilestone $promoted_milestone_builder,
        private readonly RetrieveMilestonesWithSubMilestones $dao,
    ) {
    }

    public function buildPromotedMilestoneList(PFUser $user, Planning_VirtualTopMilestone $virtual_top_milestone): PromotedMilestoneList
    {
        $project         = $virtual_top_milestone->getProject();
        $milestones_rows = $this->dao->retrieveMilestonesWithSubMilestones(
            (int) $project->getID(),
            $virtual_top_milestone->getPlanning()->getPlanningTrackerId()
        );

        $milestones = new PromotedMilestoneList();
        foreach ($milestones_rows as $row) {
            if ($milestones->isListSizeLimitReached()) {
                return $milestones;
            }

            $milestones
                ->getMilestone((int) $row['parent_id'])
                ->orElse(fn () => $this->createMilestone($row, $user, $project, $milestones))
                ->apply(fn (Planning_ArtifactMilestone $parent_milestone) => $this->createSubMilestone($parent_milestone, $row, $user, $project, $milestones));
        }

        return $milestones;
    }

    /**
     * @return Option<Planning_ArtifactMilestone>
     */
    private function createMilestone(
        array $row,
        PFUser $user,
        \Project $project,
        PromotedMilestoneList $milestones,
    ): Option {
        $artifact = $this->instantiateArtifactFromRow($row);
        $option   = $this->promoted_milestone_builder->build($artifact, $user, $project);
        $option->apply($milestones->addMilestone(...));

        return $option;
    }

    private function createSubMilestone(
        Planning_ArtifactMilestone $parent_milestone,
        array $row,
        PFUser $user,
        \Project $project,
        PromotedMilestoneList $milestones,
    ): void {
        if ($milestones->isListSizeLimitReached()) {
            return;
        }

        $this->instantiateSubArtifactFromRow($row)
            ->andThen(
                fn (Artifact $sub_artifact) =>
                $this->promoted_milestone_builder->build($sub_artifact, $user, $project)
            )
            ->apply(
                fn (Planning_ArtifactMilestone $sub_milestone) =>
                    $milestones->addSubMilestone($parent_milestone, $sub_milestone)
            );
    }

    private function instantiateArtifactFromRow(array $row): Artifact
    {
        return $this->artifact_factory->getInstanceFromRow([
            'id'                       => $row['parent_id'],
            'tracker_id'               => $row['parent_tracker'],
            'last_changeset_id'        => $row['parent_changeset'],
            'submitted_by'             => $row['parent_submitted_by'],
            'submitted_on'             => $row['parent_submitted_on'],
            'use_artifact_permissions' => $row['parent_use_artifact_permissions'],
            'per_tracker_artifact_id'  => $row['parent_per_tracker_artifact_id'],
        ]);
    }

    /**
     * @return Option<Artifact>
     */
    private function instantiateSubArtifactFromRow(array $row): Option
    {
        if (! $row['submilestone_id']) {
            return Option::nothing(Artifact::class);
        }

        return Option::fromValue(
            $this->artifact_factory->getInstanceFromRow([
                'id'                       => $row['submilestone_id'],
                'tracker_id'               => $row['submilestone_tracker'],
                'last_changeset_id'        => $row['submilestone_changeset'],
                'submitted_by'             => $row['submilestone_submitted_by'],
                'submitted_on'             => $row['submilestone_submitted_on'],
                'use_artifact_permissions' => $row['submilestone_use_artifact_permissions'],
                'per_tracker_artifact_id'  => $row['submilestone_per_tracker_artifact_id'],
            ])
        );
    }
}
