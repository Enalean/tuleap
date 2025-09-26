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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TestmanagementTrackersConfiguratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var TestmanagementTrackersConfigurator
     */
    private $tracker_configurator;

    #[\Override]
    protected function setup(): void
    {
        parent::setUp();
        $this->tracker_configurator = new TestmanagementTrackersConfigurator(
            new TestmanagementTrackersConfiguration()
        );
    }

    public function testConfigureTestmanagementTracker()
    {
        $this->tracker_configurator->configureTestmanagementTracker('campaign', 1);
        $this->tracker_configurator->configureTestmanagementTracker('test_def', 2);
        $this->tracker_configurator->configureTestmanagementTracker('test_exec', 3);
        $this->tracker_configurator->configureTestmanagementTracker('bug', 4);
        $this->tracker_configurator->configureTestmanagementTracker('Banana', 5);

        $this->assertEquals($this->getExpectedResult(), $this->tracker_configurator->getTrackersConfiguration());
    }

    private function getExpectedResult(): TestmanagementTrackersConfiguration
    {
        $tracker_configuraton = new TestmanagementTrackersConfiguration();
        $tracker_configuraton->setCampaign(
            new TestmanagementConfigTracker(
                'Validation Campaign',
                'campaign',
                1
            )
        );
        $tracker_configuraton->setTestDefinition(
            new TestmanagementConfigTracker(
                'Test Cases',
                'test_def',
                2
            )
        );
        $tracker_configuraton->setTestExecution(
            new TestmanagementConfigTracker(
                'Test Execution',
                'test_exec',
                3
            )
        );
        $tracker_configuraton->setIssue(
            new TestmanagementConfigTracker(
                'bugs',
                'bug',
                4
            )
        );

        return $tracker_configuraton;
    }
}
