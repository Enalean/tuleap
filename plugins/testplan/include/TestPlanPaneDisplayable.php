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

use AgileDashboardPlugin;
use PFUser;
use Project;
use testmanagementPlugin;
use TrackerFactory;
use Tuleap\TestManagement\Config;

class TestPlanPaneDisplayable
{
    /**
     * @var Config
     */
    private $testmanagement_config;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(Config $testmanagement_config, TrackerFactory $tracker_factory)
    {
        $this->testmanagement_config = $testmanagement_config;
        $this->tracker_factory       = $tracker_factory;
    }

    public function isTestPlanPaneDisplayable(Project $project, PFUser $user): bool
    {
        if (
            ! $project->usesService(testmanagementPlugin::SERVICE_SHORTNAME) ||
            ! $project->usesService(AgileDashboardPlugin::PLUGIN_SHORTNAME)
        ) {
            return false;
        }

        if ($this->testmanagement_config->isConfigNeeded($project)) {
            return false;
        }

        $test_definition_tracker_id = $this->testmanagement_config->getTestDefinitionTrackerId($project);
        if (! $test_definition_tracker_id) {
            return false;
        }

        $tracker = $this->tracker_factory->getTrackerById($test_definition_tracker_id);
        if (! $tracker) {
            return false;
        }

        if (! $tracker->userCanView($user)) {
            return false;
        }

        return true;
    }
}
