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
use Tuleap\TestManagement\Config;

class TestPlanPresenterBuilder
{
    /**
     * @var Planning_MilestonePaneFactory
     */
    private $pane_factory;
    /**
     * @var Config
     */
    private $testmanagement_config;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        Planning_MilestonePaneFactory $pane_factory,
        Config $testmanagement_config,
        TrackerFactory $tracker_factory
    ) {
        $this->pane_factory          = $pane_factory;
        $this->testmanagement_config = $testmanagement_config;
        $this->tracker_factory       = $tracker_factory;
    }

    public function getPresenter(\Planning_ArtifactMilestone $milestone, \PFUser $user): TestPlanPresenter
    {
        $presenter_data     = $this->pane_factory->getPanePresenterData($milestone);
        $milestone_artifact = $milestone->getArtifact();

        return new TestPlanPresenter(
            new \AgileDashboard_MilestonePresenter($milestone, $presenter_data),
            (int) $milestone_artifact->getId(),
            $milestone_artifact->getTitle() ?? '',
            (int) $milestone->getProject()->getID(),
            $this->canUserCreateACampaign($milestone->getProject(), $user),
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
