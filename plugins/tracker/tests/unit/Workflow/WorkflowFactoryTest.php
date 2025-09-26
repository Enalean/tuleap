<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\FormElement\TrackerFormElement;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorkflowFactoryTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private TrackerFormElement $field_status;

    private TrackerFormElement $field_start_date;

    private TrackerFormElement $field_close_date;

    private WorkflowFactory&MockObject $workflow_factory;

    private TransitionFactory&MockObject $transition_factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $tracker = TrackerTestBuilder::aTracker()->build();

        $this->field_status     = SelectboxFieldBuilder::aSelectboxField(1001)->inTracker($tracker)->build();
        $this->field_start_date = SelectboxFieldBuilder::aSelectboxField(1002)->inTracker($tracker)->build();
        $this->field_close_date = SelectboxFieldBuilder::aSelectboxField(1003)->inTracker($tracker)->build();

        $workflow = $this->createMock(\Workflow::class);
        $workflow->method('getFieldId')->willReturn($this->field_status->getId());

        $this->transition_factory = $this->createMock(\TransitionFactory::class);

        $this->workflow_factory = $this->getMockBuilder(\WorkflowFactory::class)
            ->onlyMethods(['getWorkflowByTrackerId'])
            ->setConstructorArgs(
                [
                    $this->transition_factory,
                    $this->createStub(TrackerFactory::class),
                    $this->createStub(Tracker_FormElementFactory::class),
                    $this->createStub(Tracker_Workflow_Trigger_RulesManager::class),
                    new WorkflowBackendLogger($this->createStub(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                    $this->createStub(FrozenFieldsDao::class),
                    $this->createStub(StateFactory::class),
                ]
            )->getMock();
        $this->workflow_factory->method('getWorkflowByTrackerId')->with($tracker->getId())->willReturn($workflow);
    }

    public function testItReturnsTrueIfTheFieldIsUsedToDescribeTheStatesOfTheWorkflow(): void
    {
        $this->transition_factory->expects($this->never())->method('isFieldUsedInTransitions');
        self::assertTrue($this->workflow_factory->isFieldUsedInWorkflow($this->field_status));
    }

    public function testItReturnsTrueIfTheFieldIsUsedInAPostAction(): void
    {
        $this->transition_factory->expects($this->once())->method('isFieldUsedInTransitions')->with($this->field_close_date)->willReturn(true);
        self::assertTrue($this->workflow_factory->isFieldUsedInWorkflow($this->field_close_date));
    }

    public function testItReturnsFalseIfTheFieldIsNotUsedByTheWorkflow(): void
    {
        $this->transition_factory->expects($this->once())->method('isFieldUsedInTransitions')->with($this->field_start_date)->willReturn(false);
        self::assertFalse($this->workflow_factory->isFieldUsedInWorkflow($this->field_start_date));
    }

    public function testItReturnsSameObjectWhenUsingSameTrackerId(): void
    {
        $tracker_rules_manager = $this->createMock(Tracker_RulesManager::class);
        $dao                   = $this->createMock(\Workflow_Dao::class);
        $tracker_factory       = $this->createMock(TrackerFactory::class);
        $workflow_factory      = $this->getMockBuilder(\WorkflowFactory::class)
            ->onlyMethods(['getDao', 'getGlobalRulesManager'])
            ->setConstructorArgs(
                [
                    $this->createStub(TransitionFactory::class),
                    $tracker_factory,
                    $this->createStub(Tracker_FormElementFactory::class),
                    $this->createStub(Tracker_Workflow_Trigger_RulesManager::class),
                    new WorkflowBackendLogger($this->createStub(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                    $this->createStub(FrozenFieldsDao::class),
                    $this->createStub(StateFactory::class),
                ]
            )->getMock();
        $workflow_factory->method('getDao')->willReturn($dao);
        $workflow_factory->method('getGlobalRulesManager')->willReturn($tracker_rules_manager);
        $tracker_factory->method('getTrackerById')->willReturn($this->createMock(Tracker::class));
        $dao->method('searchByTrackerId')->with(112)->willReturn(['tracker_id' => 112, 'workflow_id' => 34, 'field_id' => 56, 'is_used' => 1, 'is_legacy' => 0, 'is_advanced' => 1]);
        self::assertSame(
            $workflow_factory->getWorkflowByTrackerId(112),
            $workflow_factory->getWorkflowByTrackerId(112)
        );
    }
}
