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

use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\Config;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TestStatusPerTestDefinitionsInformationForUserRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Config
     */
    private $testmanagement_config;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Tracker_FormElementFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $form_element_factory;

    private TestStatusPerTestDefinitionsInformationForUserRetriever $retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->testmanagement_config = $this->createMock(Config::class);
        $this->tracker_factory       = $this->createMock(TrackerFactory::class);
        $this->form_element_factory  = $this->createMock(Tracker_FormElementFactory::class);

        $this->retriever = new TestStatusPerTestDefinitionsInformationForUserRetriever(
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory,
        );
    }

    public function testAUserCanObtainTheNeededInformationIfSheHasEnoughRights(): void
    {
        $milestone    = $this->buildMilestone();
        $user         = $this->createMock(\PFUser::class);
        $user_ugroups = ['123', 4];
        $user->method('getUgroups')->willReturn($user_ugroups);

        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $status_field      = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('getId')->willReturn(4444);
        $status_field->method('userCanRead')->willReturn(true);
        $test_exec_tracker->method('getStatusField')->willReturn($status_field);
        $artifact_link = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $artifact_link->method('getId')->willReturn(852);

        $this->testmanagement_config->method('getCampaignTrackerId')->willReturn(13);
        $test_campaign_tracker = $this->buildTracker(true);

        $artifact_link2 = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $artifact_link2->method('getId')->willReturn(258);

        $this->form_element_factory->method('getAnArtifactLinkField')->willReturnMap(
            [
                [$user, $test_campaign_tracker, $artifact_link2],
                [$user, $test_exec_tracker, $artifact_link],
            ],
        );

        $this->tracker_factory->method('getTrackerById')->willReturnMap(
            [
                [13, $test_campaign_tracker],
                [11, $test_exec_tracker],
            ],
        );

        $test_definitions = $this->buildTestDefinitions();

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            $user,
            $milestone,
            $test_definitions
        );
        self::assertEquals(
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
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $status_field      = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('getId')->willReturn(4444);
        $status_field->method('userCanRead')->willReturn(true);
        $test_exec_tracker->method('getStatusField')->willReturn($status_field);

        $this->testmanagement_config->method('getCampaignTrackerId')->willReturn(13);
        $test_campaign_tracker = $this->buildTracker(true);

        $this->tracker_factory->method('getTrackerById')->willReturnMap(
            [
                [13, $test_campaign_tracker],
                [11, $test_exec_tracker],
            ],
        );

        $this->form_element_factory->method('getAnArtifactLinkField')
            ->willReturn(null);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        self::assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenSheCannotViewCampaignTracker(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $status_field      = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('getId')->willReturn(4444);
        $status_field->method('userCanRead')->willReturn(true);
        $test_exec_tracker->method('getStatusField')->willReturn($status_field);
        $artifact_link = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $artifact_link->method('getId')->willReturn(852);
        $this->form_element_factory->method('getAnArtifactLinkField')
            ->with($user, $test_exec_tracker)
            ->willReturn($artifact_link);

        $this->testmanagement_config->method('getCampaignTrackerId')->willReturn(13);
        $test_campaign_tracker = $this->buildTracker(false);

        $this->tracker_factory->method('getTrackerById')->willReturnMap(
            [
                [13, $test_campaign_tracker],
                [11, $test_exec_tracker],
            ],
        );

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            $user,
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        self::assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenTTMConfigDoNotDefineCampaignTracker(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->method('getTrackerById')->with(11)->willReturn($test_exec_tracker);
        $status_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('getId')->willReturn(4444);
        $status_field->method('userCanRead')->willReturn(true);
        $test_exec_tracker->method('getStatusField')->willReturn($status_field);
        $artifact_link = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $artifact_link->method('getId')->willReturn(852);
        $this->form_element_factory->method('getAnArtifactLinkField')
            ->with($user, $test_exec_tracker)
            ->willReturn($artifact_link);

        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(false);
        $this->testmanagement_config->method('getCampaignTrackerId')->willReturn(false);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            $user,
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        self::assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenSheCannotReadTestExecStatusField(): void
    {
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->method('getTrackerById')->with(11)->willReturn($test_exec_tracker);
        $status_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('getId')->willReturn(4444);
        $status_field->method('userCanRead')->willReturn(false);
        $test_exec_tracker->method('getStatusField')->willReturn($status_field);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        self::assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenTestExecStatusFieldDoesNotExist(): void
    {
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->method('getTrackerById')->with(11)->willReturn($test_exec_tracker);
        $test_exec_tracker->method('getStatusField')->willReturn(null);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        self::assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenSheCannotViewTestExecTracker(): void
    {
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(false);
        $this->tracker_factory->method('getTrackerById')->with(11)->willReturn($test_exec_tracker);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        self::assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenTestExecTrackerDoesNotExist(): void
    {
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $this->tracker_factory->method('getTrackerById')->with(11)->willReturn(null);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        self::assertNull($information);
    }

    public function testUserCannotAccessNeededInformationWhenTTMConfigDoesNotDefineATestExecTracker(): void
    {
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(false);

        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            $this->buildTestDefinitions()
        );
        self::assertNull($information);
    }

    public function testNoNeedToSearchTheInformationWhenTheyAreNoTestDefinitions(): void
    {
        $information = $this->retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            UserTestBuilder::aUser()->build(),
            $this->buildMilestone(),
            []
        );
        self::assertNull($information);
    }

    private function buildMilestone(): \Tuleap\Tracker\Artifact\Artifact
    {
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn(852);
        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);
        $tracker->method('getProject')->willReturn($project);
        $tracker->method('userCanView')->willReturn(true);
        $artifact->method('getTracker')->willReturn($tracker);

        return $artifact;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Tracker
     */
    private function buildTracker(bool $can_user_view_it): \Tuleap\Tracker\Tracker
    {
        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('userCanView')->willReturn($can_user_view_it);

        return $tracker;
    }

    /**
     * @return \Tuleap\Tracker\Artifact\Artifact[]
     *
     * @psalm-return non-empty-array<\Tuleap\Tracker\Artifact\Artifact>
     */
    private function buildTestDefinitions(): array
    {
        $test_definition_1 = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $test_definition_1->method('getId')->willReturn('694');
        $test_definition_2 = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $test_definition_2->method('getId')->willReturn('695');

        return [$test_definition_1, $test_definition_2];
    }
}
