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

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Workflow_Trigger_RulesBuilderFactory;
use Tracker_Workflow_Trigger_RulesDao;
use Tracker_Workflow_Trigger_RulesManager;
use Tracker_Workflow_Trigger_RulesProcessor;
use Tracker_Workflow_Trigger_TriggerRule;

final class WorkflowRulesManagerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown() : void
    {
        Tracker_ArtifactFactory::clearInstance();
    }

    public function testCanNotLoopInfinitelyWhileProcessingChildrenTriggers() : void
    {
        $rules_dao       = Mockery::mock(Tracker_Workflow_Trigger_RulesDao::class);
        $rules_processor = Mockery::mock(Tracker_Workflow_Trigger_RulesProcessor::class);
        $logger          = new WorkflowBackendLogger(new \Psr\Log\NullLogger(), \Psr\Log\LogLevel::ERROR);

        $rules_manager = Mockery::mock(
            Tracker_Workflow_Trigger_RulesManager::class . '[getRuleById]',
            [
                $rules_dao,
                Mockery::mock(Tracker_FormElementFactory::class),
                $rules_processor,
                $logger,
                Mockery::mock(Tracker_Workflow_Trigger_RulesBuilderFactory::class),
                new WorkflowRulesManagerLoopSafeGuard($logger)
            ]
        );

        $rule = Mockery::mock(Tracker_Workflow_Trigger_TriggerRule::class);
        $rule->shouldReceive('fetchFormattedForJson')->andReturn('');
        $rules_manager->shouldReceive('getRuleById')->andReturn($rule);
        $rules_dao->shouldReceive('searchForInvolvedRulesForChildrenLastChangeset')
            ->andReturn([['rule_id' => 963]]);

        $parent_artifact = Mockery::mock(Tracker_Artifact::class);
        $parent_artifact->shouldReceive('getId')->andReturn(147);

        $artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);
        $artifact_factory->shouldReceive('getInstanceFromRow')->andReturn($parent_artifact);
        Tracker_ArtifactFactory::setInstance($artifact_factory);

        $rules_processor->shouldReceive('process')->withArgs(
            static function (Tracker_Artifact $artifact) use ($rules_manager) : bool {
                $rules_manager->processChildrenTriggers($artifact);
                return true;
            }
        )->once();

        $rules_manager->processChildrenTriggers($parent_artifact);
    }
}
