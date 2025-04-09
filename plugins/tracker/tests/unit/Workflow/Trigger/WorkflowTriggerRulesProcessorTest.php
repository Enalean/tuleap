<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\Trigger;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset;
use Tracker_Workflow_Trigger_FieldValue;
use Tracker_Workflow_Trigger_RulesBuilderData;
use Tracker_Workflow_Trigger_RulesProcessor;
use Tracker_Workflow_Trigger_TriggerRule;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsRetriever;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

#[DisableReturnValueGenerationForTestDoubles]
final class WorkflowTriggerRulesProcessorTest extends TestCase
{
    public function testRulesProcessorDoesNotLoopWhenUpdatingAnArtifactParentWithItself(): void
    {
        $workflow_user = $this->createMock(Tracker_Workflow_WorkflowUser::class);
        $processor     = new Tracker_Workflow_Trigger_RulesProcessor(
            $workflow_user,
            $this->createMock(SiblingsRetriever::class),
            new WorkflowBackendLogger(new NullLogger(), LogLevel::ERROR)
        );

        $target_tracker = TrackerTestBuilder::aTracker()->build();

        $rule = $this->createMock(Tracker_Workflow_Trigger_TriggerRule::class);
        $rule->method('getId')->willReturn(1);
        $rule->method('getTargetTracker')->willReturn($target_tracker);
        $field_value = $this->createMock(Tracker_Workflow_Trigger_FieldValue::class);
        $field_value->method('isSetForArtifact')->willReturn(false);
        $field_value->method('getFieldData')->willReturn([]);
        $rule->method('getTarget')->willReturn($field_value);
        $rule->method('getCondition')->willReturn(Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE);
        $rule->method('getAsChangesetComment')->willReturn('');

        $artifact_1 = $this->createMock(Artifact::class);
        $artifact_2 = $this->createMock(Artifact::class);
        $artifact_2->method('getTrackerId')->willReturn(852);
        $this->prepareArtifactMockToBeProcessed(
            $artifact_1,
            147,
            $artifact_2,
            $rule,
            $processor
        );
        $this->prepareArtifactMockToBeProcessed(
            $artifact_2,
            258,
            $artifact_1,
            $rule,
            $processor
        );

        $this->expectNotToPerformAssertions();

        $processor->process($artifact_1, $rule);
    }

    private function prepareArtifactMockToBeProcessed(
        MockObject&Artifact $artifact_mock,
        int $artifact_id,
        Artifact $parent,
        Tracker_Workflow_Trigger_TriggerRule $rule,
        Tracker_Workflow_Trigger_RulesProcessor $processor,
    ): void {
        $artifact_mock->method('getId')->willReturn($artifact_id);
        $artifact_mock->method('getXRef')->willReturn('xref #' . $artifact_id);
        $artifact_mock->method('getParentWithoutPermissionChecking')->willReturn($parent);
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getUri')->willReturn('');
        $artifact_mock->method('getLastChangeset')->willReturn($changeset);
        $artifact_mock->method('createNewChangeset')->willReturnCallback(
            static function () use ($processor, $parent, $rule): void {
                $processor->process($parent, $rule);
            }
        );
    }

    public function testRuleOnlyUpdatesAParentArtifactIfItIsInTheExpectedTargetedTracker(): void
    {
        $workflow_user = $this->createMock(Tracker_Workflow_WorkflowUser::class);
        $processor     = new Tracker_Workflow_Trigger_RulesProcessor(
            $workflow_user,
            $this->createMock(SiblingsRetriever::class),
            new WorkflowBackendLogger(new NullLogger(), LogLevel::ERROR)
        );

        $target_tracker = TrackerTestBuilder::aTracker()->build();
        $rule           = $this->createMock(Tracker_Workflow_Trigger_TriggerRule::class);
        $rule->method('getId')->willReturn(1);
        $rule->method('getTargetTracker')->willReturn($target_tracker);
        $field_value = $this->createMock(Tracker_Workflow_Trigger_FieldValue::class);
        $field_value->method('isSetForArtifact')->willReturn(false);
        $rule->method('getTarget')->willReturn($field_value);
        $rule->method('getCondition')->willReturn(Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(123);
        $artifact->method('getXRef')->willReturn('xref #' . 123);
        $parent_artifact = $this->createMock(Artifact::class);
        $artifact->method('getParentWithoutPermissionChecking')->willReturn($parent_artifact);
        $parent_artifact->method('getTrackerId')->willReturn(963);
        $parent_artifact->method('getId')->willReturn(122);
        $parent_artifact->method('getXRef')->willReturn('xref #' . 122);

        $parent_artifact->expects($this->never())->method('createNewChangeset');

        $processor->process($artifact, $rule);
    }
}
