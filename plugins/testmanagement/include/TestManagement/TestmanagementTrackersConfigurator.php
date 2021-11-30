<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

class TestmanagementTrackersConfigurator
{
    public const CAMPAIGN_TRACKER_NAME   = "Validation Campaign";
    public const DEFINITION_TRACKER_NAME = "Test Cases";
    public const EXECUTION_TRACKER_NAME  = "Test Execution";
    public const ISSUE_TRACKER_NAME      = "bugs";

    /**
     * @var TestmanagementTrackersConfiguration
     */
    private $testmanagement_trackers_configuration;

    public function __construct(
        TestmanagementTrackersConfiguration $testmanagement_trackers_configuration,
    ) {
        $this->testmanagement_trackers_configuration = $testmanagement_trackers_configuration;
    }

    public function configureTestmanagementTracker(string $tracker_shortname, int $tracker_id): ?TestmanagementConfigTracker
    {
        switch ($tracker_shortname) {
            case CAMPAIGN_TRACKER_SHORTNAME:
                $this->testmanagement_trackers_configuration->setCampaign(
                    new TestmanagementConfigTracker(
                        self::CAMPAIGN_TRACKER_NAME,
                        CAMPAIGN_TRACKER_SHORTNAME,
                        $tracker_id
                    )
                );
                break;
            case DEFINITION_TRACKER_SHORTNAME:
                $this->testmanagement_trackers_configuration->setTestDefinition(
                    new TestmanagementConfigTracker(
                        self::DEFINITION_TRACKER_NAME,
                        DEFINITION_TRACKER_SHORTNAME,
                        $tracker_id
                    )
                );
                break;
            case EXECUTION_TRACKER_SHORTNAME:
                $this->testmanagement_trackers_configuration->setTestExecution(
                    new TestmanagementConfigTracker(
                        self::EXECUTION_TRACKER_NAME,
                        EXECUTION_TRACKER_SHORTNAME,
                        $tracker_id
                    )
                );
                break;
            case ISSUE_TRACKER_SHORTNAME:
                $this->testmanagement_trackers_configuration->setIssue(
                    new TestmanagementConfigTracker(
                        self::ISSUE_TRACKER_NAME,
                        ISSUE_TRACKER_SHORTNAME,
                        $tracker_id
                    )
                );
                break;
        }

        return null;
    }

    public function getTrackersConfiguration(): TestmanagementTrackersConfiguration
    {
        return $this->testmanagement_trackers_configuration;
    }
}
