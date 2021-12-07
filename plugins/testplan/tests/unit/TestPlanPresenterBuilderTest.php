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
use Planning_ArtifactMilestone;
use Planning_Milestone;
use Planning_MilestonePaneFactory;
use TrackerFactory;
use Tuleap\AgileDashboard\Milestone\Pane\PanePresenterData;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\IRetrieveAllUsableTypesInProject;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter;

final class TestPlanPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

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
        \ForgeConfig::set('sys_name', 'Tuleap');
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
        $this->milestone->shouldReceive('getGroupId')->andReturn(102);
        $this->milestone->shouldReceive('getPlanningId')->andReturn(111);
        $this->milestone->shouldReceive('getArtifactId')->andReturn(999);
        $this->milestone->shouldReceive('getParent')->andReturnNull()->byDefault();

        $this->testmanagement_config       = \Mockery::mock(Config::class);
        $this->tracker_factory             = \Mockery::mock(TrackerFactory::class);
        $test_definition_tracker_retriever = \Mockery::mock(TestPlanTestDefinitionTrackerRetriever::class);
        $test_def_tracker                  = \Mockery::mock(\Tracker::class);
        $test_def_tracker->shouldReceive('getId')->andReturn(146);
        $test_def_tracker->shouldReceive('getName')->andReturn('Test Def');
        $test_definition_tracker_retriever->shouldReceive('getTestDefinitionTracker')->andReturn($test_def_tracker);
        $user_helper = \Mockery::mock(\UserHelper::class);
        $user_helper->shouldReceive('getDisplayNameFromUser')->andReturn('User Name');

        $type_presenter_factory = new class implements IRetrieveAllUsableTypesInProject {
            public function getAllUsableTypesInProject(\Project $project): array
            {
                return [
                    new TypeIsChildPresenter(),
                ];
            }
        };

        $this->builder = new TestPlanPresenterBuilder(
            $pane_factory,
            $this->testmanagement_config,
            $this->tracker_factory,
            $test_definition_tracker_retriever,
            $user_helper,
            $type_presenter_factory,
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

    public function testBuildsPresenterWithParentMilestoneTitleIfAny(): void
    {
        $parent_artifact = $this->createMock(Planning_Milestone::class);
        $parent_artifact->method('getArtifactTitle')->willReturn("Parent 01");

        $this->milestone->shouldReceive('getParent')->andReturn($parent_artifact);

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

        $this->assertSame("Parent 01", $presenter->parent_milestone_title);
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

    public function testItExportsArtifactLinkTypesJsonEncoded(): void
    {
        $this->testmanagement_config->shouldReceive('getCampaignTrackerId')->andReturn(false);
        $presenter = $this->builder->getPresenter(
            $this->milestone,
            UserTestBuilder::aUser()->build(),
            42,
            101,
        );

        $this->assertEquals(
            '[{"reverse_label":"Parent","forward_label":"Child","shortname":"_is_child","is_system":true,"is_visible":true}]',
            $presenter->artifact_links_types
        );
    }
}
