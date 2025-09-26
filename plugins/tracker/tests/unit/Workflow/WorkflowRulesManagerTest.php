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

namespace Tuleap\Tracker\Workflow;

use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Workflow_Trigger_RulesBuilderFactory;
use Tracker_Workflow_Trigger_RulesDao;
use Tracker_Workflow_Trigger_RulesManager;
use Tracker_Workflow_Trigger_RulesProcessor;
use Tracker_Workflow_Trigger_TriggerRule;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorkflowRulesManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        Tracker_ArtifactFactory::clearInstance();
    }

    public function testCanNotLoopInfinitelyWhileProcessingChildrenTriggers(): void
    {
        $rules_dao       = $this->createMock(Tracker_Workflow_Trigger_RulesDao::class);
        $rules_processor = $this->createMock(Tracker_Workflow_Trigger_RulesProcessor::class);
        $logger          = new WorkflowBackendLogger(new \Psr\Log\NullLogger(), \Psr\Log\LogLevel::ERROR);

        $rules_manager = $this->getMockBuilder(Tracker_Workflow_Trigger_RulesManager::class)
            ->onlyMethods(['getRuleById'])
            ->setConstructorArgs(
                [
                    $rules_dao,
                    $this->createMock(Tracker_FormElementFactory::class),
                    $rules_processor,
                    $logger,
                    $this->createMock(Tracker_Workflow_Trigger_RulesBuilderFactory::class),
                    new WorkflowRulesManagerLoopSafeGuard($logger),
                ]
            )->getMock();

        $rule = $this->createMock(Tracker_Workflow_Trigger_TriggerRule::class);
        $rule->method('fetchFormattedForJson')->willReturn('');
        $rules_manager->method('getRuleById')->willReturn($rule);
        $rules_dao->method('searchForInvolvedRulesForChildrenLastChangeset')
            ->willReturn([['rule_id' => 963]]);

        $parent_artifact = $this->createMock(Artifact::class);
        $parent_artifact->method('getId')->willReturn(147);

        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifact_factory->method('getInstanceFromRow')->willReturn($parent_artifact);
        Tracker_ArtifactFactory::setInstance($artifact_factory);

        $rules_processor->expects($this->once())->method('process')->willReturnCallback(
            static function (Artifact $artifact) use ($rules_manager): bool {
                $rules_manager->processChildrenTriggers($artifact);
                return true;
            }
        );

        $rules_manager->processChildrenTriggers($parent_artifact);
    }
}
