<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Trafficlights;

use MVC2_PluginController;
use Codendi_Request;
use Planning_Milestone;
use PFUser;

abstract class TrafficlightsController extends MVC2_PluginController {

    const NAME = 'trafficlights';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Project
     */
    protected $project;

    /**
     * @var Planning_Milestone
     */
    protected $current_milestone;

    public function __construct(Codendi_Request $request, Config $config) {
        parent::__construct(self::NAME, $request);

        $this->project           = $request->getProject();
        $this->config            = $config;

        $this->current_milestone = $this->getMilestone(
            $request->getCurrentUser(),
            (int)$request->getValidated('milestone_id', 'int', 0)
        );
    }

    public function getBreadcrumbs() {
        return new NoCrumb();
    }

    protected function getTemplatesDir() {
        return TRAFFICLIGHTS_BASE_DIR.'/templates';
    }

    /**
     * @param PFuser  $current_user The user that can view the milestone
     * @param int     $milestone_id The id of the milestone to retrieve
     *
     * @return Planning_Milestone|null
     */
    private function getMilestone(PFUser $current_user, $milestone_id)
    {
        $artifact_factory = \Tracker_ArtifactFactory::instance();
        $status_counter   = new \AgileDashboard_Milestone_MilestoneStatusCounter(
            new \AgileDashboard_BacklogItemDao(),
            new \Tracker_ArtifactDao(),
            $artifact_factory
        );
        $planning_factory = \PlanningFactory::build();

        $milestone_factory = new \Planning_MilestoneFactory(
            $planning_factory,
            $artifact_factory,
            \Tracker_FormElementFactory::instance(),
            \TrackerFactory::instance(),
            $status_counter,
            new \PlanningPermissionsManager(),
            new \AgileDashboard_Milestone_MilestoneDao(),
            new \Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker(
                new \Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao(),
                $planning_factory
            )
        );

        return $milestone_factory->getBareMilestoneByArtifactId(
            $current_user,
            $milestone_id
        );
    }
}
