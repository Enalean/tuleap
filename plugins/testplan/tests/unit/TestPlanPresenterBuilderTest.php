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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TestPlanPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private TestPlanPresenterBuilder $builder;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Planning_ArtifactMilestone
     */
    private $milestone;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Config
     */
    private $testmanagement_config;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        \ForgeConfig::set(\Tuleap\Config\ConfigurationVariables::NAME, 'Tuleap');
        $pane_factory = $this->createMock(Planning_MilestonePaneFactory::class);
        $pane_factory->method('getPanePresenterData')->willReturn($this->createMock(PanePresenterData::class));

        $this->milestone = $this->createMock(Planning_ArtifactMilestone::class);
        $artifact        = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn('999');
        $artifact->method('getTitle')->willReturn('Milestone title');
        $this->milestone->method('getArtifact')->willReturn($artifact);
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn('102');
        $project->method('getPublicName')->willReturn('Project public name');
        $this->milestone->method('getProject')->willReturn($project);
        $this->milestone->method('getGroupId')->willReturn(102);
        $this->milestone->method('getPlanningId')->willReturn(111);
        $this->milestone->method('getArtifactId')->willReturn(999);

        $this->testmanagement_config       = $this->createMock(Config::class);
        $this->tracker_factory             = $this->createMock(TrackerFactory::class);
        $test_definition_tracker_retriever = $this->createMock(TestPlanTestDefinitionTrackerRetriever::class);
        $test_def_tracker                  = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $test_def_tracker->method('getId')->willReturn(146);
        $test_def_tracker->method('getName')->willReturn('Test Def');
        $test_definition_tracker_retriever->method('getTestDefinitionTracker')->willReturn($test_def_tracker);
        $user_helper = $this->createMock(\UserHelper::class);
        $user_helper->method('getDisplayNameFromUser')->willReturn('User Name');

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
        $this->milestone->method('getParent')->willReturn(null);
        $this->testmanagement_config->method('getCampaignTrackerId')->willReturn(145);
        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);
        $tracker->method('userCanSubmitArtifact')->willReturn(true);

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
        $parent_artifact->method('getArtifactTitle')->willReturn('Parent 01');

        $this->milestone->method('getParent')->willReturn($parent_artifact);

        $this->testmanagement_config->method('getCampaignTrackerId')->willReturn(145);
        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);
        $tracker->method('userCanSubmitArtifact')->willReturn(true);

        $presenter = $this->builder->getPresenter(
            $this->milestone,
            UserTestBuilder::aUser()->build(),
            42,
            101,
        );

        self::assertSame('Parent 01', $presenter->parent_milestone_title);
    }

    public function testBuildsPresenterWithAUserThatDoesNotHaveEnoughPermissionsToCreateACampaign(): void
    {
        $this->milestone->method('getParent')->willReturn(null);
        $this->testmanagement_config->method('getCampaignTrackerId')->willReturn(145);
        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);
        $tracker->method('userCanSubmitArtifact')->willReturn(false);

        $presenter = $this->builder->getPresenter(
            $this->milestone,
            UserTestBuilder::aUser()->build(),
            42,
            101,
        );

        self::assertFalse($presenter->user_can_create_campaign);
    }

    public function testBuildsPresenterWithATestManagementConfigWithoutACampaignTrackerID(): void
    {
        $this->milestone->method('getParent')->willReturn(null);
        $this->testmanagement_config->method('getCampaignTrackerId')->willReturn(false);

        $presenter = $this->builder->getPresenter(
            $this->milestone,
            UserTestBuilder::aUser()->build(),
            42,
            101,
        );

        self::assertFalse($presenter->user_can_create_campaign);
    }

    public function testBuildsPresenterWhenTheCampaignTrackerCannotBeInstantiated(): void
    {
        $this->milestone->method('getParent')->willReturn(null);
        $this->testmanagement_config->method('getCampaignTrackerId')->willReturn(145);
        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        $presenter = $this->builder->getPresenter(
            $this->milestone,
            UserTestBuilder::aUser()->build(),
            42,
            101,
        );

        self::assertFalse($presenter->user_can_create_campaign);
    }

    public function testItExportsArtifactLinkTypesJsonEncoded(): void
    {
        $this->milestone->method('getParent')->willReturn(null);
        $this->testmanagement_config->method('getCampaignTrackerId')->willReturn(false);
        $presenter = $this->builder->getPresenter(
            $this->milestone,
            UserTestBuilder::aUser()->build(),
            42,
            101,
        );

        self::assertEquals(
            '[{"reverse_label":"Parent","forward_label":"Child","shortname":"_is_child","is_system":true,"is_visible":true}]',
            $presenter->artifact_links_types
        );
    }
}
