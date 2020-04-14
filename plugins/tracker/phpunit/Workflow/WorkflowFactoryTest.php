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

use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

final class WorkflowFactoryTest extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_FormElement */
    private $field_status;

    /** @var Tracker_FormElement */
    private $field_start_date;

    /** @var Tracker_FormElement */
    private $field_close_date;

    /** @var WorkflowFactory */
    private $workflow_factory;

    /** @var TransitionFactory */
    private $transition_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $tracker = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(123)->getMock();

        $this->field_status     = $this->setUpField($tracker, 1001);
        $this->field_start_date = $this->setUpField($tracker, 1002);
        $this->field_close_date = $this->setUpField($tracker, 1003);

        $workflow = \Mockery::spy(\Workflow::class);
        $workflow->shouldReceive('getFieldId')->andReturns($this->field_status->getId());

        $this->transition_factory = \Mockery::spy(\TransitionFactory::class);

        $this->workflow_factory = \Mockery::mock(
            \WorkflowFactory::class . '[getWorkflowByTrackerId]',
            [
                $this->transition_factory,
                Mockery::mock(TrackerFactory::class),
                Mockery::mock(Tracker_FormElementFactory::class),
                Mockery::mock(Tracker_Workflow_Trigger_RulesManager::class),
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                Mockery::mock(FrozenFieldsDao::class),
                Mockery::mock(StateFactory::class),
            ]
        );
        $this->workflow_factory->shouldReceive('getWorkflowByTrackerId')->with($tracker->getId())->andReturns($workflow);
    }

    private function setUpField(Tracker $tracker, $id)
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $field->shouldReceive('getTracker')->andReturns($tracker);
        $field->shouldReceive('getId')->andReturns($id);
        return $field;
    }

    public function testItReturnsTrueIfTheFieldIsUsedToDescribeTheStatesOfTheWorkflow(): void
    {
        $this->transition_factory->shouldReceive('isFieldUsedInTransitions')->never();
        $this->assertTrue($this->workflow_factory->isFieldUsedInWorkflow($this->field_status));
    }

    public function testItReturnsTrueIfTheFieldIsUsedInAPostAction(): void
    {
        $this->transition_factory->shouldReceive('isFieldUsedInTransitions')->with($this->field_close_date)->once()->andReturns(true);
        $this->assertTrue($this->workflow_factory->isFieldUsedInWorkflow($this->field_close_date));
    }

    public function testItReturnsFalseIfTheFieldIsNotUsedByTheWorkflow(): void
    {
        $this->transition_factory->shouldReceive('isFieldUsedInTransitions')->with($this->field_start_date)->once()->andReturns(false);
        $this->assertFalse($this->workflow_factory->isFieldUsedInWorkflow($this->field_start_date));
    }

    public function testItReturnsSameObjectWhenUsingSameTrackerId(): void
    {
        $tracker_rules_manager = Mockery::mock(Tracker_RulesManager::class);
        $dao = \Mockery::spy(\Workflow_Dao::class);
        $tracker_factory = Mockery::mock(TrackerFactory::class);
        $workflow_factory = \Mockery::mock(
            \WorkflowFactory::class . '[getDao,getGlobalRulesManager]',
            [
                Mockery::mock(TransitionFactory::class),
                $tracker_factory,
                Mockery::mock(Tracker_FormElementFactory::class),
                Mockery::mock(Tracker_Workflow_Trigger_RulesManager::class),
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                \Mockery::mock(FrozenFieldsDao::class),
                Mockery::mock(StateFactory::class),
            ]
        )->shouldAllowMockingProtectedMethods();
        $workflow_factory->shouldReceive('getDao')->andReturns($dao);
        $workflow_factory->shouldReceive('getGlobalRulesManager')->andReturns($tracker_rules_manager);
        $tracker_factory->shouldReceive('getTrackerById')->andReturn(Mockery::mock(Tracker::class));
        $dao->shouldReceive('searchByTrackerId')->with(112)->andReturns(\TestHelper::arrayToDar(array('tracker_id' => 112, 'workflow_id' => 34, 'field_id' => 56, 'is_used' => 1, 'is_legacy' => 0, 'is_advanced' => 1)));
        $this->assertSame(
            $workflow_factory->getWorkflowByTrackerId(112),
            $workflow_factory->getWorkflowByTrackerId(112)
        );
    }
}
