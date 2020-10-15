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

namespace Tuleap\TestPlan\TestDefinition;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\Config;

final class TestStatusPerTestDefinitionsInformationForUserRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Config
     */
    private $testmanagement_config;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var TestStatusPerTestDefinitionsInformationForUserRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->testmanagement_config = \Mockery::mock(Config::class);
        $this->tracker_factory       = \Mockery::mock(TrackerFactory::class);
        $this->form_element_factory  = \Mockery::mock(Tracker_FormElementFactory::class);

        $this->retriever = new TestStatusPerTestDefinitionsInformationForUserRetriever(
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory,
        );
    }

    public function testAUserCanObtainTheNeededInformationIfSheHasEnoughRights(): void
    {
        $milestone    = $this->buildMilestone();
        $user         = \Mockery::mock(\PFUser::class);
        $user_ugroups = ['123', 4];
        $user->shouldReceive('getUgroups')->andReturn($user_ugroups);

        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);
        $status_field = \Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getId')->andReturn(4444);
        $status_field->shouldReceive('userCanRead')->andReturn(true);
        $test_exec_tracker->shouldReceive('getStatusField')->andReturn($status_field);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->with($user, $test_exec_tracker)
            ->andReturn(
                \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->shouldReceive('getId')->andReturn(852)->getMock()
            );

        $this->testmanagement_config->shouldReceive('getCampaignTrackerId')->andReturn(13);
        $test_campaign_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(13)->andReturn($test_campaign_tracker);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->with($user, $test_campaign_tracker)
            ->andReturn(
                \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->shouldReceive('getId')->andReturn(258)->getMock()
            );

        $test_definitions = $this->buildTestDefinitions();

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            $user,
            $milestone,
            $test_definitions
        );
        $this->assertEquals(
            new TestPlanMilestoneInformationNeededToRetrieveTestStatusPerTestDefinition(
                $milestone,
                $test_definitions,
                $user_ugroups,
                4444,
                852,
                258
            ),
            $information
        );
    }

    public function testUserCannotAccessNeededInformationWhenSheCannotReadArtLinkInTestExecOrCampaignTracker(): void
    {
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);
        $status_field = \Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getId')->andReturn(4444);
        $status_field->shouldReceive('userCanRead')->andReturn(true);
        $test_exec_tracker->shouldReceive('getStatusField')->andReturn($status_field);

        $this->testmanagement_config->shouldReceive('getCampaignTrackerId')->andReturn(13);
        $test_campaign_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(13)->andReturn($test_campaign_tracker);

        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->andReturn(null);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        $this->assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenSheCannotViewCampaignTracker(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);
        $status_field = \Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getId')->andReturn(4444);
        $status_field->shouldReceive('userCanRead')->andReturn(true);
        $test_exec_tracker->shouldReceive('getStatusField')->andReturn($status_field);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->with($user, $test_exec_tracker)
            ->andReturn(
                \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->shouldReceive('getId')->andReturn(852)->getMock()
            );

        $this->testmanagement_config->shouldReceive('getCampaignTrackerId')->andReturn(13);
        $test_campaign_tracker = $this->buildTracker(false);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(13)->andReturn($test_campaign_tracker);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            $user,
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        $this->assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenTTMConfigDoNotDefineCampaignTracker(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);
        $status_field = \Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getId')->andReturn(4444);
        $status_field->shouldReceive('userCanRead')->andReturn(true);
        $test_exec_tracker->shouldReceive('getStatusField')->andReturn($status_field);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->with($user, $test_exec_tracker)
            ->andReturn(
                \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->shouldReceive('getId')->andReturn(852)->getMock()
            );

        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturn(false);
        $this->testmanagement_config->shouldReceive('getCampaignTrackerId')->andReturn(false);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            $user,
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        $this->assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenSheCannotReadTestExecStatusField(): void
    {
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);
        $status_field = \Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getId')->andReturn(4444);
        $status_field->shouldReceive('userCanRead')->andReturn(false);
        $test_exec_tracker->shouldReceive('getStatusField')->andReturn($status_field);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        $this->assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenTestExecStatusFieldDoesNotExist(): void
    {
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);
        $test_exec_tracker->shouldReceive('getStatusField')->andReturn(null);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        $this->assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenSheCannotViewTestExecTracker(): void
    {
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(false);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        $this->assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenTestExecTrackerDoesNotExist(): void
    {
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn(null);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        $this->assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenTTMConfigDoesNotDefineATestExecTracker(): void
    {
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(false);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        $this->assertNull($information);
    }

    public function testNoNeedToSearchTheInformationWhenTheyAreNoTestDefinitions(): void
    {
        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            []
        );
        $this->assertNull($information);
    }

    private function buildMilestone(): \Tuleap\Tracker\Artifact\Artifact
    {
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(852);
        $tracker  = \Mockery::mock(\Tracker::class);
        $project  = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $tracker->shouldReceive('userCanView')->andReturn(true);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        return $artifact;
    }

    /**
     * @return \Tracker&\Mockery\MockInterface
     */
    private function buildTracker(bool $can_user_view_it): \Tracker
    {
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('userCanView')->andReturn($can_user_view_it);

        return $tracker;
    }

    /**
     * @return \Tuleap\Tracker\Artifact\Artifact[]
     */
    private function buildTestDefinitions(): array
    {
        $test_definition_1 = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->shouldReceive('getId')->andReturn('694')->getMock();
        $test_definition_2 = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->shouldReceive('getId')->andReturn('695')->getMock();

        return [$test_definition_1, $test_definition_2];
    }
}
