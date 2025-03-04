<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Administration;

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tracker;
use TrackerFactory;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AdminTrackersRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TrackerFactory&MockObject $tracker_factory;
    private TrackerChecker&MockObject $tracker_checker;
    private Config&MockObject $config;
    private AdminTrackersRetriever $admin_trackers_retriever;
    private Project&MockObject $project;
    private Tracker $tracker_2;
    private Tracker $tracker_1;

    protected function setUp(): void
    {
        $this->tracker_1 = TrackerTestBuilder::aTracker()->withId(13)->withName('tracker_1')->build();
        $this->tracker_2 = TrackerTestBuilder::aTracker()->withId(14)->withName('tracker_2')->build();

        $this->project = $this->createMock(Project::class);
        $this->project->method('getGroupId')->willReturn(444);

        $this->tracker_factory = $this->createMock(TrackerFactory::class);
        $this->tracker_checker = $this->createMock(TrackerChecker::class);
        $this->config          = $this->createMock(Config::class);

        $this->admin_trackers_retriever = new AdminTrackersRetriever(
            $this->tracker_factory,
            $this->tracker_checker,
            $this->config
        );
    }

    public function testGetAvailableTrackersForCampaign(): void
    {
        $tracker_campaign = TrackerTestBuilder::aTracker()->withId(12)->withName('chosen tracker')->build();

        $trackers_available = [$this->tracker_1, $this->tracker_2];

        $this->config->method('getCampaignTrackerId')->willReturn(12);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker_campaign);

        $this->tracker_factory->method('getTrackersByGroupId')->willReturn($trackers_available);

        $this->tracker_checker->method('checkSubmittedTrackerCanBeUsed');

        $expected_result = new ListOfAdminTrackersPresenter(
            new AdminTrackerPresenter('chosen tracker', 12),
            [
                new AdminTrackerPresenter('tracker_1', 13),
                new AdminTrackerPresenter('tracker_2', 14),
            ]
        );

        $this->assertEquals(
            $expected_result,
            $this->admin_trackers_retriever->retrieveAvailableTrackersForCampaign($this->project)
        );
    }

    public function testGetAvailableTrackersForCampaignIfNoTrackerAreSetForAdministration(): void
    {
        $trackers_available = [$this->tracker_1, $this->tracker_2];

        $this->config->method('getCampaignTrackerId')->willReturn(12);
        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        $this->tracker_factory->method('getTrackersByGroupId')->willReturn($trackers_available);

        $this->tracker_checker->method('checkSubmittedTrackerCanBeUsed');

        $expected_result = new ListOfAdminTrackersPresenter(
            null,
            [
                new AdminTrackerPresenter('tracker_1', 13),
                new AdminTrackerPresenter('tracker_2', 14),
            ]
        );

        $this->assertEquals(
            $expected_result,
            $this->admin_trackers_retriever->retrieveAvailableTrackersForCampaign($this->project)
        );
    }

    public function testGetAvailableTrackersForExecution(): void
    {
        $tracker_execution = TrackerTestBuilder::aTracker()->withId(12)->withName('chosen tracker')->build();

        $trackers_available = [$this->tracker_1, $this->tracker_2];

        $this->config->method('getTestExecutionTrackerId')->willReturn(12);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker_execution);

        $this->tracker_factory->method('getTrackersByGroupId')->willReturn($trackers_available);

        $this->tracker_checker->method('checkSubmittedExecutionTrackerCanBeUsed');

        $expected_result = new ListOfAdminTrackersPresenter(
            new AdminTrackerPresenter('chosen tracker', 12),
            [
                new AdminTrackerPresenter('tracker_1', 13),
                new AdminTrackerPresenter('tracker_2', 14),
            ]
        );

        $this->assertEquals(
            $expected_result,
            $this->admin_trackers_retriever->retrieveAvailableTrackersForExecution($this->project)
        );
    }

    public function testGetAvailableTrackersForDefinition(): void
    {
        $tracker_definition = TrackerTestBuilder::aTracker()->withId(12)->withName('chosen tracker')->build();

        $trackers_available = [$this->tracker_1, $this->tracker_2];

        $this->config->method('getTestDefinitionTrackerId')->willReturn(12);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker_definition);

        $this->tracker_factory->method('getTrackersByGroupId')->willReturn($trackers_available);

        $this->tracker_checker->method('checkSubmittedDefinitionTrackerCanBeUsed');

        $expected_result = new ListOfAdminTrackersPresenter(
            new AdminTrackerPresenter('chosen tracker', 12),
            [
                new AdminTrackerPresenter('tracker_1', 13),
                new AdminTrackerPresenter('tracker_2', 14),
            ]
        );

        $this->assertEquals(
            $expected_result,
            $this->admin_trackers_retriever->retrieveAvailableTrackersForDefinition($this->project)
        );
    }

    public function testGetAvailableTrackersForIssue(): void
    {
        $tracker_issue = TrackerTestBuilder::aTracker()->withId(12)->withName('chosen tracker')->build();

        $trackers_available = [$this->tracker_1, $this->tracker_2];

        $this->config->method('getIssueTrackerId')->willReturn(12);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker_issue);

        $this->tracker_factory->method('getTrackersByGroupId')->willReturn($trackers_available);

        $this->tracker_checker->method('checkSubmittedTrackerCanBeUsed');

        $expected_result = new ListOfAdminTrackersPresenter(
            new AdminTrackerPresenter('chosen tracker', 12),
            [
                new AdminTrackerPresenter('tracker_1', 13),
                new AdminTrackerPresenter('tracker_2', 14),
            ]
        );

        $this->assertEquals(
            $expected_result,
            $this->admin_trackers_retriever->retrieveAvailableTrackersForIssue($this->project)
        );
    }
}
