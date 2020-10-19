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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning_ArtifactMilestone;
use Planning_MilestonePaneFactory;
use TrackerFactory;
use Tuleap\AgileDashboard\Milestone\Pane\PanePresenterData;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\Config;

final class TestPlanPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_ArtifactMilestone
     */
    private $milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Config
     */
    private $testmanagement_config;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var TestPlanPresenterBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $pane_factory = \Mockery::mock(Planning_MilestonePaneFactory::class);
        $pane_factory->shouldReceive('getPanePresenterData')->andReturn(\Mockery::mock(PanePresenterData::class));

        $this->milestone = \Mockery::mock(Planning_ArtifactMilestone::class);
        $artifact        = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn('999');
        $artifact->shouldReceive('getTitle')->andReturn('Milestone title');
        $this->milestone->shouldReceive('getArtifact')->andReturn($artifact);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn('102');
        $project->shouldReceive('getPublicName')->andReturn('Project public name');
        $this->milestone->shouldReceive('getProject')->andReturn($project);

        $this->testmanagement_config       = \Mockery::mock(Config::class);
        $this->tracker_factory             = \Mockery::mock(TrackerFactory::class);
        $test_definition_tracker_retriever = \Mockery::mock(TestPlanTestDefinitionTrackerRetriever::class);
        $test_def_tracker                  = \Mockery::mock(\Tracker::class);
        $test_def_tracker->shouldReceive('getId')->andReturn(146);
        $test_def_tracker->shouldReceive('getName')->andReturn('Test Def');
        $test_definition_tracker_retriever->shouldReceive('getTestDefinitionTracker')->andReturn($test_def_tracker);
        $user_helper = \Mockery::mock(\UserHelper::class);
        $user_helper->shouldReceive('getDisplayNameFromUser')->andReturn('User Name');

        $this->builder = new TestPlanPresenterBuilder(
            $pane_factory,
            $this->testmanagement_config,
            $this->tracker_factory,
            $test_definition_tracker_retriever,
            $user_helper
        );
    }

    public function testBuildsPresenterWithAUserAbleToCreateACampaign(): void
    {
        $this->testmanagement_config->shouldReceive('getCampaignTrackerId')->andReturn(145);
        $tracker = \Mockery::mock(\Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($tracker);
        $tracker->shouldReceive('userCanSubmitArtifact')->andReturn(true);

        $presenter = $this->builder->getPresenter(
            $this->milestone,
            UserTestBuilder::aUser()->build(),
            42,
            101,
        );

        $this->assertTrue($presenter->user_can_create_campaign);
    }

    public function testBuildsPresenterWithAUserThatDoesNotHaveEnoughPermissionsToCreateACampaign(): void
    {
        $this->testmanagement_config->shouldReceive('getCampaignTrackerId')->andReturn(145);
        $tracker = \Mockery::mock(\Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($tracker);
        $tracker->shouldReceive('userCanSubmitArtifact')->andReturn(false);

        $presenter = $this->builder->getPresenter(
            $this->milestone,
            UserTestBuilder::aUser()->build(),
            42,
            101,
        );

        $this->assertFalse($presenter->user_can_create_campaign);
    }

    public function testBuildsPresenterWithATestManagementConfigWithoutACampaignTrackerID(): void
    {
        $this->testmanagement_config->shouldReceive('getCampaignTrackerId')->andReturn(false);

        $presenter = $this->builder->getPresenter(
            $this->milestone,
            UserTestBuilder::aUser()->build(),
            42,
            101,
        );

        $this->assertFalse($presenter->user_can_create_campaign);
    }

    public function testBuildsPresenterWhenTheCampaignTrackerCannotBeInstantiated(): void
    {
        $this->testmanagement_config->shouldReceive('getCampaignTrackerId')->andReturn(145);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn(null);

        $presenter = $this->builder->getPresenter(
            $this->milestone,
            UserTestBuilder::aUser()->build(),
            42,
            101,
        );

        $this->assertFalse($presenter->user_can_create_campaign);
    }
}
