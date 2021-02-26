<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\MonoMilestone;

use PFUser;
use PlanningFactory;

class ScrumForMonoMilestoneChecker
{
    public const NUMBER_MAXIMUM_OF_PLANNING = 1;

    /**
     * Feature flag to allow users to configure scrum v2 (mono milestone) in their projects
     *
     * @tlp-config-feature-flag-key
     */
    public const FEATURE_FLAG_KEY = 'allow_scrum_mono_milestone_usage';

    /**
     * @var ScrumForMonoMilestoneDao
     */
    private $scrum_mono_milestaone_dao;
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(ScrumForMonoMilestoneDao $scrum_mono_milestaone_dao, PlanningFactory $planning_factory)
    {
        $this->scrum_mono_milestaone_dao = $scrum_mono_milestaone_dao;
        $this->planning_factory          = $planning_factory;
    }

    public function isMonoMilestoneEnabled($project_id)
    {
        $row = $this->scrum_mono_milestaone_dao->isMonoMilestoneActivatedForProject($project_id);
        if (! $row) {
            return false;
        }

        return true;
    }

    public function doesScrumMonoMilestoneConfigurationAllowsPlanningCreation(PFUser $user, $project_id)
    {
        if ($this->isMonoMilestoneEnabled($project_id) === false) {
            return true;
        }

        return $this->isMonoMilestoneEnabled($project_id) === true
            && $this->isOneOrLessPlanningDefined($user, $project_id, self::NUMBER_MAXIMUM_OF_PLANNING) === true;
    }

    public function isOneOrLessPlanningDefined(PFUser $user, $project_id, $maximum_planning_allowed_by_configuration)
    {
        return count($this->planning_factory->getPlannings($user, $project_id)) < $maximum_planning_allowed_by_configuration;
    }

    public function isScrumMonoMilestoneAvailable(PFUser $user, $project_id)
    {
        return $this->isMonoMilestoneEnabled($project_id) === true ||
            (
                \ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_KEY)
                && $this->isOneOrLessPlanningDefined($user, $project_id, self::NUMBER_MAXIMUM_OF_PLANNING + 1) === true
            );
    }
}
