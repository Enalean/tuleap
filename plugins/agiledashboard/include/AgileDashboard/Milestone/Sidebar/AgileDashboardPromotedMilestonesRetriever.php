<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

use DateTime;
use PFUser;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Planning_NoPlanningsException;
use PlanningFactory;
use Project;
use Psr\Log\LoggerInterface;
use Tracker_ArtifactFactory;
use Tracker_Semantic_Title;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\Layout\SidebarPromotedItemPresenter;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use function Psl\Type\non_empty_string;
use function Psl\Type\shape;
use function Psl\Type\string;

final class AgileDashboardPromotedMilestonesRetriever
{
    public function __construct(
        private readonly Planning_MilestoneFactory $milestone_factory,
        private readonly RetrieveMilestonesWithSubMilestones $dao,
        private readonly Project $project,
        private readonly CheckMilestonesInSidebar $milestones_in_sidebar,
        private readonly Tracker_ArtifactFactory $artifact_factory,
        private readonly PlanningFactory $planning_factory,
        private readonly ScrumForMonoMilestoneChecker $milestone_checker,
        private readonly SemanticTimeframeBuilder $timeframe_builder,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<SidebarPromotedItemPresenter>
     */
    public function getSidebarPromotedMilestones(PFUser $user): array
    {
        if (! $this->milestones_in_sidebar->shouldSidebarDisplayLastMilestones((int) $this->project->getID())) {
            return [];
        }

        try {
            $virtual_milestone = $this->milestone_factory->getVirtualTopMilestone($user, $this->project);
        } catch (Planning_NoPlanningsException) {
            return [];
        }
        $milestone_planning_tracker_id = $virtual_milestone->getPlanning()->getPlanningTrackerId();

        $milestones_rows = $this->dao->retrieveMilestonesWithSubMilestones((int) $this->project->getID(), $milestone_planning_tracker_id);
        $milestones      = $this->convertDBRowsToArrayOfMilestones($milestones_rows, $user);

        $items = [];
        foreach ($milestones as $milestone_struct) {
            $milestone      = $milestone_struct['milestone'];
            $sub_milestones = $milestone_struct['sub_milestones'];

            $data_milestone = $this->getDataForMilestone($milestone);
            if ($data_milestone->isNothing()) {
                continue;
            }

            $sub_milestones_items = [];
            foreach ($sub_milestones as $sub_milestone) {
                $data_sub_milestone = $this->getDataForMilestone($sub_milestone);
                $data_sub_milestone->apply(function ($data) use (&$sub_milestones_items) {
                    $sub_milestones_items[] = new SidebarPromotedItemPresenter(
                        $data['uri'],
                        $data['title'],
                        $data['description'],
                        false,
                        null,
                        []
                    );
                });
            }

            $data_milestone->apply(function ($data) use (&$items, $sub_milestones_items) {
                $items[] = new SidebarPromotedItemPresenter(
                    $data['uri'],
                    $data['title'],
                    $data['description'],
                    false,
                    null,
                    $sub_milestones_items
                );
            });
        }

        return $items;
    }

    /**
     * @return Option<array{
     *     uri: non-empty-string,
     *     title: non-empty-string,
     *     description: string
     * }>
     */
    private function getDataForMilestone(Planning_ArtifactMilestone $milestone): Option
    {
        $artifact = $milestone->getArtifact();

        $title = $artifact->getTitle();
        if ($title === null || $title === '') {
            return Option::nothing(shape([
                'uri'         => non_empty_string(),
                'title'       => non_empty_string(),
                'description' => string(),
            ]));
        }

        $description = $artifact->getDescription();

        $uri = '/plugins/agiledashboard/?' . http_build_query([
            'group_id'    => $this->project->getID(),
            'planning_id' => $milestone->getPlanningId(),
            'action'      => 'show',
            'aid'         => $artifact->getId(),
            'pane'        => PlanningV2PaneInfo::IDENTIFIER,
        ]);

        return Option::fromValue([
            'uri'         => $uri,
            'title'       => $title,
            'description' => $description,
        ]);
    }

    /**
     * @return array<int, array{
     *      milestone: Planning_ArtifactMilestone,
     *      sub_milestones: Planning_ArtifactMilestone[]
     *  }>
     */
    private function convertDBRowsToArrayOfMilestones(array $milestones_rows, PFUser $user): array
    {
        /**
         * @var array<int, array{
         *     milestone: Planning_ArtifactMilestone,
         *     sub_milestones: Planning_ArtifactMilestone[]
         * }> $milestones
         */
        $milestones = [];
        $count      = 0;
        foreach ($milestones_rows as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow([
                'id'                       => $row['parent_id'],
                'tracker_id'               => $row['parent_tracker'],
                'last_changeset_id'        => $row['parent_changeset'],
                'submitted_by'             => $row['parent_submitted_by'],
                'submitted_on'             => $row['parent_submitted_on'],
                'use_artifact_permissions' => $row['parent_use_artifact_permissions'],
                'per_tracker_artifact_id'  => $row['parent_per_tracker_artifact_id'],
            ]);
            if (! array_key_exists($artifact->getId(), $milestones)) {
                $this->getMilestoneFromArtifact($artifact, $user)->apply(function ($milestone) use (&$milestones, &$count, $artifact) {
                    $milestones[$artifact->getId()] = [
                        'milestone'      => $milestone,
                        'sub_milestones' => [],
                    ];
                    $count++;
                });
            }

            if (array_key_exists('submilestone_id', $row) && $row['submilestone_id'] !== null) {
                $sub_artifact = $this->artifact_factory->getInstanceFromRow([
                    'id'                       => $row['submilestone_id'],
                    'tracker_id'               => $row['submilestone_tracker'],
                    'last_changeset_id'        => $row['submilestone_changeset'],
                    'submitted_by'             => $row['submilestone_submitted_by'],
                    'submitted_on'             => $row['submilestone_submitted_on'],
                    'use_artifact_permissions' => $row['submilestone_use_artifact_permissions'],
                    'per_tracker_artifact_id'  => $row['submilestone_per_tracker_artifact_id'],
                ]);

                $this->getMilestoneFromArtifact($sub_artifact, $user)->apply(function ($sub_milestone) use (&$milestones, &$count, $artifact) {
                    $milestones[$artifact->getId()]['sub_milestones'][] = $sub_milestone;
                    $count++;
                });
            }

            if ($count == 5) {
                break;
            }
        }

        return $milestones;
    }

    /**
     * @return Option<Planning_ArtifactMilestone>
     */
    private function getMilestoneFromArtifact(Artifact $artifact, PFUser $user): Option
    {
        if (! $artifact->userCanView($user)) {
            return Option::nothing(Planning_ArtifactMilestone::class);
        }

        $title_field = Tracker_Semantic_Title::load($artifact->getTracker())->getField();
        if ($title_field === null) {
            return Option::nothing(Planning_ArtifactMilestone::class);
        }

        $timeframe = $this->timeframe_builder->getSemantic($artifact->getTracker());
        if (! $timeframe->isDefined()) {
            return Option::nothing(Planning_ArtifactMilestone::class);
        }
        $date_period  = $timeframe->getTimeframeCalculator()->buildDatePeriodWithoutWeekendForChangeset(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );
        $current_date = (new DateTime())->getTimestamp();
        if (! ($date_period->getStartDate() <= $current_date && $date_period->getEndDate() >= $current_date)) {
            return Option::nothing(Planning_ArtifactMilestone::class);
        }

        $planning = $this->planning_factory->getPlanningByPlanningTracker($artifact->getTracker());
        if (! $planning) {
            return Option::nothing(Planning_ArtifactMilestone::class);
        }

        return Option::fromValue(new Planning_ArtifactMilestone(
            $this->project,
            $planning,
            $artifact,
            $this->milestone_checker
        ));
    }
}
