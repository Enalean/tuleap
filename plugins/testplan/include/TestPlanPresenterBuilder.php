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

namespace Tuleap\TestPlan;

use Planning_MilestonePaneFactory;
use TrackerFactory;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\IRetrieveAllUsableTypesInProject;
use UserHelper;

class TestPlanPresenterBuilder
{
    public function __construct(
        private Planning_MilestonePaneFactory $pane_factory,
        private Config $testmanagement_config,
        private TrackerFactory $tracker_factory,
        private TestPlanTestDefinitionTrackerRetriever $definition_tracker_retriever,
        private UserHelper $user_helper,
        private IRetrieveAllUsableTypesInProject $type_presenter_factory,
    ) {
    }

    public function getPresenter(
        \Planning_ArtifactMilestone $milestone,
        \PFUser $user,
        int $expand_backlog_item_id,
        int $highlight_test_definition_id,
    ): TestPlanPresenter {
        $presenter_data     = $this->pane_factory->getPanePresenterData($milestone);
        $milestone_artifact = $milestone->getArtifact();
        $project            = $milestone->getProject();

        $parent_milestone       = $milestone->getParent();
        $parent_milestone_title = "";
        if ($parent_milestone) {
            $parent_milestone_title = $parent_milestone->getArtifactTitle() ?? "";
        }

        $details_pane = new DetailsPaneInfo($milestone);

        return new TestPlanPresenter(
            new \AgileDashboard_MilestonePresenter($milestone, $presenter_data),
            $this->user_helper->getDisplayNameFromUser($user),
            (int) $milestone_artifact->getId(),
            $milestone_artifact->getTitle() ?? '',
            $parent_milestone_title,
            \Tuleap\ServerHostname::HTTPSUrl() . $details_pane->getUri(),
            (int) $project->getID(),
            $project->getPublicName(),
            $this->canUserCreateACampaign($project, $user),
            $this->definition_tracker_retriever->getTestDefinitionTracker($project, $user),
            $expand_backlog_item_id,
            $highlight_test_definition_id,
            \ForgeConfig::get('sys_name'),
            \Admin_Homepage_LogoFinder::getCurrentUrl(),
            \Tuleap\ServerHostname::HTTPSUrl(),
            $this->type_presenter_factory->getAllUsableTypesInProject($project),
        );
    }

    private function canUserCreateACampaign(\Project $project, \PFUser $user): bool
    {
        $campaign_tracker_id = $this->testmanagement_config->getCampaignTrackerId($project);
        if ($campaign_tracker_id === false) {
            return false;
        }
        $campaign_tracker = $this->tracker_factory->getTrackerById($campaign_tracker_id);
        if ($campaign_tracker === null) {
            return false;
        }

        return $campaign_tracker->userCanSubmitArtifact($user);
    }
}
