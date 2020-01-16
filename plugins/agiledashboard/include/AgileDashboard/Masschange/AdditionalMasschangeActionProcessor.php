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
use Tracker;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactAlreadyPlannedException;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;

class AdditionalMasschangeActionProcessor
{
    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var PlannedArtifactDao
     */
    private $planned_artifact_dao;

    /**
     * @var UnplannedArtifactsAdder
     */
    private $unplanned_artifacts_adder;

    public function __construct(
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        PlannedArtifactDao $planned_artifact_dao,
        UnplannedArtifactsAdder $unplanned_artifacts_adder
    ) {
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
        $this->planned_artifact_dao              = $planned_artifact_dao;
        $this->unplanned_artifacts_adder         = $unplanned_artifacts_adder;
    }

    public function processAction(
        PFUser $user,
        Tracker $tracker,
        Codendi_Request $request,
        array $masschange_aids
    ): void {
        if (! $tracker->userIsAdmin($user)) {
            return;
        }

        if (! $request->exist('masschange-action-explicit-backlog')) {
            return;
        }

        $project_id = (int) $tracker->getProject()->getID();

        $action = $request->get('masschange-action-explicit-backlog');
        if ($action === 'unchanged') {
            return;
        } elseif ($action === 'remove') {
            $this->removeUnplannedArtifactsToTopBacklog($masschange_aids, $project_id);
        } elseif ($action === 'add') {
            $this->addUnplannedArtifactsToTopBacklog($masschange_aids, $project_id);
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                dgettext('tuleap-agiledashboard', 'Unknown masschange action.')
            );
        }
    }

    private function removeUnplannedArtifactsToTopBacklog(array $masschange_aids, int $project_id): void
    {
        foreach ($masschange_aids as $masschange_aid) {
            $artifact_id = (int) $masschange_aid;
            if ($this->planned_artifact_dao->isArtifactPlannedInAMilestoneOfTheProject($artifact_id, $project_id)) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    sprintf(
                        dgettext(
                            'tuleap-agiledashboard',
                            "Artifact #%d not removed from the top backlog because it's already planned in a submilestone."
                        ),
                        $artifact_id
                    )
                );
            }
        }

        $this->artifacts_in_explicit_backlog_dao->removeItemsFromExplicitBacklogOfProject(
            $project_id,
            $masschange_aids
        );
    }

    private function addUnplannedArtifactsToTopBacklog(array $masschange_aids, int $project_id): void
    {
        foreach ($masschange_aids as $masschange_aid) {
            $artifact_id = (int) $masschange_aid;

            try {
                $this->unplanned_artifacts_adder->addArtifactToTopBacklogFromIds(
                    $artifact_id,
                    $project_id
                );
            } catch (ArtifactAlreadyPlannedException $exception) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    sprintf(
                        dgettext(
                            'tuleap-agiledashboard',
                            "Artifact #%d not added in the top backlog because it's already planned in a submilestone."
                        ),
                        $artifact_id
                    )
                );
            }
        }
    }
}
