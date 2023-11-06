<?php
/**
 * Copyright (c) Enalean 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Masschange;

use Codendi_Request;
use Feedback;
use PFUser;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactAlreadyPlannedException;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;

class AdditionalMasschangeActionProcessor
{
    public function __construct(
        private readonly ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        private readonly PlannedArtifactDao $planned_artifact_dao,
        private readonly UnplannedArtifactsAdder $unplanned_artifacts_adder,
        private readonly EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function processAction(
        PFUser $user,
        Tracker $tracker,
        Codendi_Request $request,
        array $masschange_aids,
    ): void {
        if (! $tracker->userIsAdmin($user)) {
            return;
        }

        $block_scrum_access = new \Tuleap\AgileDashboard\BlockScrumAccess($tracker->getProject());
        $this->event_dispatcher->dispatch($block_scrum_access);
        if (! $block_scrum_access->isScrumAccessEnabled()) {
            return;
        }

        if (! $request->exist('masschange-action-explicit-backlog')) {
            return;
        }

        $project = $tracker->getProject();

        $action = $request->get('masschange-action-explicit-backlog');
        if ($action === 'unchanged') {
            return;
        } elseif ($action === 'remove') {
            $this->removeUnplannedArtifactsToTopBacklog($masschange_aids, $project);
        } elseif ($action === 'add') {
            $this->addUnplannedArtifactsToTopBacklog($masschange_aids, $project);
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                dgettext('tuleap-agiledashboard', 'Unknown masschange action.')
            );
        }
    }

    private function removeUnplannedArtifactsToTopBacklog(array $masschange_aids, Project $project): void
    {
        foreach ($masschange_aids as $masschange_aid) {
            $artifact_id = (int) $masschange_aid;
            if ($this->planned_artifact_dao->isArtifactPlannedInAMilestoneOfTheProject($artifact_id, (int) $project->getID())) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    sprintf(
                        dgettext(
                            'tuleap-agiledashboard',
                            "Artifact #%d not removed from the backlog because it's already planned in a submilestone."
                        ),
                        $artifact_id
                    )
                );
            }
        }

        $this->artifacts_in_explicit_backlog_dao->removeItemsFromExplicitBacklogOfProject(
            (int) $project->getID(),
            $masschange_aids
        );
    }

    private function addUnplannedArtifactsToTopBacklog(array $masschange_aids, Project $project): void
    {
        foreach ($masschange_aids as $masschange_aid) {
            $artifact_id = (int) $masschange_aid;

            try {
                $this->unplanned_artifacts_adder->addArtifactToTopBacklogFromIds(
                    $artifact_id,
                    (int) $project->getID()
                );
            } catch (ArtifactAlreadyPlannedException $exception) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    sprintf(
                        dgettext(
                            'tuleap-agiledashboard',
                            "Artifact #%d not added in the backlog because it's already planned in a submilestone."
                        ),
                        $artifact_id
                    )
                );
            }
        }
    }
}
