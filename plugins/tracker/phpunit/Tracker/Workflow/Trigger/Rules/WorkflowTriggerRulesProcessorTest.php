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

use Log_NoopLogger;
use Logger;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Workflow_Trigger_FieldValue;
use Tracker_Workflow_Trigger_RulesBuilderData;
use Tracker_Workflow_Trigger_RulesProcessor;
use Tracker_Workflow_Trigger_TriggerRule;
use Tracker_Workflow_WorkflowUser;
use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

final class WorkflowTriggerRulesProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    public function testRulesProcessorDoesNotLoopWhenUpdatingAnArtifactParentWithItself() : void
    {
        $workflow_user = Mockery::mock(Tracker_Workflow_WorkflowUser::class);
        $processor     = new Tracker_Workflow_Trigger_RulesProcessor(
            $workflow_user,
            new WorkflowBackendLogger(new Log_NoopLogger(), Logger::ERROR)
        );

        $rule = Mockery::mock(Tracker_Workflow_Trigger_TriggerRule::class);
        $rule->shouldReceive('getId')->andReturn(1);
        $field_value = Mockery::mock(Tracker_Workflow_Trigger_FieldValue::class);
        $field_value->shouldReceive('isSetForArtifact')->andReturn(false);
        $field_value->shouldReceive('getFieldData')->andReturn([]);
        $rule->shouldReceive('getTarget')->andReturn($field_value);
        $rule->shouldReceive('getCondition')->andReturn(Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE);
        $rule->shouldReceive('getAsChangesetComment')->andReturn('');

        $artifact_1 = Mockery::mock(Tracker_Artifact::class);
        $artifact_2 = Mockery::mock(Tracker_Artifact::class);
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

        $processor->process($artifact_1, $rule);
    }

    private function prepareArtifactMockToBeProcessed(
        Mockery\MockInterface $artifact_mock,
        int $artifact_id,
        Tracker_Artifact $parent,
        Tracker_Workflow_Trigger_TriggerRule $rule,
        Tracker_Workflow_Trigger_RulesProcessor $processor
    ) : void {
        $artifact_mock->shouldReceive('getId')->andReturn($artifact_id);
        $artifact_mock->shouldReceive('getXRef')->andReturn('xref #' . $artifact_id);
        $artifact_mock->shouldReceive('getParentWithoutPermissionChecking')->andReturn($parent);
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getUri')->andReturn('');
        $artifact_mock->shouldReceive('getLastChangeset')->andReturn($changeset);
        $artifact_mock->shouldReceive('createNewChangeset')->andReturnUsing(
            static function () use ($processor, $parent, $rule) : void {
                $processor->process($parent, $rule);
            }
        );
    }
}
