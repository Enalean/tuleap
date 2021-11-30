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

use Mockery;
use Project;
use Tracker;
use TrackerFactory;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\MissingArtifactLinkException;
use Tuleap\TestManagement\TrackerDefinitionNotValidException;
use Tuleap\TestManagement\TrackerExecutionNotValidException;

class AdminTrackersRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerChecker
     */
    private $tracker_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Config
     */
    private $config;
    /**
     * @var AdminTrackersRetriever
     */
    private $admin_trackers_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker_2;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker_1;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker_3;

    protected function setUp(): void
    {
        $this->tracker_1 = Mockery::mock(Tracker::class);
        $this->tracker_2 = Mockery::mock(Tracker::class);
        $this->tracker_3 = Mockery::mock(Tracker::class);

        $this->tracker_1->shouldReceive("getId")->andReturn(13);
        $this->tracker_1->shouldReceive("getName")->andReturn("tracker_1");
        $this->tracker_2->shouldReceive("getId")->andReturn(14);
        $this->tracker_2->shouldReceive("getName")->andReturn("tracker_2");
        $this->tracker_3->shouldReceive("getId")->andReturn(15);

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive("getGroupId")->andReturn(444);

        $this->tracker_factory = Mockery::mock(TrackerFactory::class);
        $this->tracker_checker = Mockery::mock(TrackerChecker::class);
        $this->config          = Mockery::mock(Config::class);

        $this->admin_trackers_retriever = new AdminTrackersRetriever(
            $this->tracker_factory,
            $this->tracker_checker,
            $this->config
        );
    }

    public function testGetAvailableTrackersForCampaign(): void
    {
        $tracker_campaign = Mockery::mock(Tracker::class);
        $tracker_campaign->shouldReceive("getId")->andReturn(12);
        $tracker_campaign->shouldReceive("getName")->andReturn("chosen tracker");

        $trackers_available = [$this->tracker_1, $this->tracker_2];

        $this->config->shouldReceive("getCampaignTrackerId")->andReturn(12);
        $this->tracker_factory->shouldReceive("getTrackerById")->andReturn($tracker_campaign);

        $this->tracker_factory->shouldReceive("getTrackersByGroupId")->andReturn($trackers_available);

        $this->tracker_checker->shouldReceive("checkSubmittedTrackerCanBeUsed")->withArgs([$this->project, 13]);
        $this->tracker_checker->shouldReceive("checkSubmittedTrackerCanBeUsed")->withArgs([$this->project, 14]);
        $this->tracker_checker->shouldReceive("checkSubmittedTrackerCanBeUsed")->withArgs(
            [$this->project, 15]
        )->andThrow(MissingArtifactLinkException::class);

        $expected_result = new ListOfAdminTrackersPresenter(
            new AdminTrackerPresenter("chosen tracker", 12),
            [
                new AdminTrackerPresenter("tracker_1", 13),
                new AdminTrackerPresenter("tracker_2", 14),
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

        $this->config->shouldReceive("getCampaignTrackerId")->andReturn(12);
        $this->tracker_factory->shouldReceive("getTrackerById")->andReturn(null);

        $this->tracker_factory->shouldReceive("getTrackersByGroupId")->andReturn($trackers_available);

        $this->tracker_checker->shouldReceive("checkSubmittedTrackerCanBeUsed")->withArgs([$this->project, 13]);
        $this->tracker_checker->shouldReceive("checkSubmittedTrackerCanBeUsed")->withArgs([$this->project, 14]);
        $this->tracker_checker->shouldReceive("checkSubmittedTrackerCanBeUsed")->withArgs(
            [$this->project, 15]
        )->andThrow(MissingArtifactLinkException::class);

        $expected_result = new ListOfAdminTrackersPresenter(
            null,
            [
                new AdminTrackerPresenter("tracker_1", 13),
                new AdminTrackerPresenter("tracker_2", 14),
            ]
        );

        $this->assertEquals(
            $expected_result,
            $this->admin_trackers_retriever->retrieveAvailableTrackersForCampaign($this->project)
        );
    }

    public function testGetAvailableTrackersForExecution(): void
    {
        $tracker_execution = Mockery::mock(Tracker::class);
        $tracker_execution->shouldReceive("getId")->andReturn(12);
        $tracker_execution->shouldReceive("getName")->andReturn("chosen tracker");

        $trackers_available = [$this->tracker_1, $this->tracker_2];

        $this->config->shouldReceive("getTestExecutionTrackerId")->andReturn(12);
        $this->tracker_factory->shouldReceive("getTrackerById")->andReturn($tracker_execution);

        $this->tracker_factory->shouldReceive("getTrackersByGroupId")->andReturn($trackers_available);

        $this->tracker_checker->shouldReceive("checkSubmittedExecutionTrackerCanBeUsed")->withArgs([$this->project, 13]);
        $this->tracker_checker->shouldReceive("checkSubmittedExecutionTrackerCanBeUsed")->withArgs([$this->project, 14]);
        $this->tracker_checker->shouldReceive("checkSubmittedExecutionTrackerCanBeUsed")->withArgs(
            [$this->project, 15]
        )->andThrow(TrackerExecutionNotValidException::class);

        $expected_result = new ListOfAdminTrackersPresenter(
            new AdminTrackerPresenter("chosen tracker", 12),
            [
                new AdminTrackerPresenter("tracker_1", 13),
                new AdminTrackerPresenter("tracker_2", 14),
            ]
        );

        $this->assertEquals(
            $expected_result,
            $this->admin_trackers_retriever->retrieveAvailableTrackersForExecution($this->project)
        );
    }

    public function testGetAvailableTrackersForDefinition(): void
    {
        $tracker_definition = Mockery::mock(Tracker::class);
        $tracker_definition->shouldReceive("getId")->andReturn(12);
        $tracker_definition->shouldReceive("getName")->andReturn("chosen tracker");

        $trackers_available = [$this->tracker_1, $this->tracker_2];

        $this->config->shouldReceive("getTestDefinitionTrackerId")->andReturn(12);
        $this->tracker_factory->shouldReceive("getTrackerById")->andReturn($tracker_definition);

        $this->tracker_factory->shouldReceive("getTrackersByGroupId")->andReturn($trackers_available);

        $this->tracker_checker->shouldReceive("checkSubmittedDefinitionTrackerCanBeUsed")->withArgs([$this->project, 13]);
        $this->tracker_checker->shouldReceive("checkSubmittedDefinitionTrackerCanBeUsed")->withArgs([$this->project, 14]);
        $this->tracker_checker->shouldReceive("checkSubmittedDefinitionTrackerCanBeUsed")->withArgs(
            [$this->project, 15]
        )->andThrow(TrackerDefinitionNotValidException::class);

        $expected_result = new ListOfAdminTrackersPresenter(
            new AdminTrackerPresenter("chosen tracker", 12),
            [
                new AdminTrackerPresenter("tracker_1", 13),
                new AdminTrackerPresenter("tracker_2", 14),
            ]
        );

        $this->assertEquals(
            $expected_result,
            $this->admin_trackers_retriever->retrieveAvailableTrackersForDefinition($this->project)
        );
    }
    public function testGetAvailableTrackersForIssue(): void
    {
        $tracker_issue = Mockery::mock(Tracker::class);
        $tracker_issue->shouldReceive("getId")->andReturn(12);
        $tracker_issue->shouldReceive("getName")->andReturn("chosen tracker");

        $trackers_available = [$this->tracker_1, $this->tracker_2];

        $this->config->shouldReceive("getIssueTrackerId")->andReturn(12);
        $this->tracker_factory->shouldReceive("getTrackerById")->andReturn($tracker_issue);

        $this->tracker_factory->shouldReceive("getTrackersByGroupId")->andReturn($trackers_available);

        $this->tracker_checker->shouldReceive("checkSubmittedTrackerCanBeUsed")->withArgs([$this->project, 13]);
        $this->tracker_checker->shouldReceive("checkSubmittedTrackerCanBeUsed")->withArgs([$this->project, 14]);
        $this->tracker_checker->shouldReceive("checkSubmittedTrackerCanBeUsed")->withArgs(
            [$this->project, 15]
        )->andThrow(MissingArtifactLinkException::class);

        $expected_result = new ListOfAdminTrackersPresenter(
            new AdminTrackerPresenter("chosen tracker", 12),
            [
                new AdminTrackerPresenter("tracker_1", 13),
                new AdminTrackerPresenter("tracker_2", 14),
            ]
        );

        $this->assertEquals(
            $expected_result,
            $this->admin_trackers_retriever->retrieveAvailableTrackersForIssue($this->project)
        );
    }
}
