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

use TrackerFactory;
use Tuleap\TestManagement\Config;

class TestPlanTestDefinitionTrackerRetriever
{
    /**
     * @var Config
     */
    private $testmanagement_config;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        Config $testmanagement_config,
        TrackerFactory $tracker_factory
    ) {
        $this->testmanagement_config = $testmanagement_config;
        $this->tracker_factory       = $tracker_factory;
    }

    public function getTestDefinitionTracker(\Project $project, \PFUser $user): ?\Tracker
    {
        $test_definition_tracker_id = $this->testmanagement_config->getTestDefinitionTrackerId($project);
        if ($test_definition_tracker_id === false) {
            return null;
        }
        $test_definition_tracker = $this->tracker_factory->getTrackerById($test_definition_tracker_id);
        if ($test_definition_tracker === null) {
            return null;
        }
        if (! $test_definition_tracker->userCanView($user)) {
            return null;
        }

        return $test_definition_tracker;
    }
}
