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

use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

require_once __DIR__ . '/../bootstrap.php';

Mock::generate('Tracker');
Mock::generate('Workflow');
Mock::generate('Workflow_Dao');
Mock::generate('TransitionFactory');

Mock::generate('Tracker_FormElement_Field_List');

class WorkflowFactory_IsFieldUsedInWorkflowTest extends TuleapTestCase
{

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

    public function setUp()
    {
        parent::setUp();
        $tracker = stub('Tracker')->getId()->returns(123);

        $this->field_status     = $this->setUpField($tracker, 1001);
        $this->field_start_date = $this->setUpField($tracker, 1002);
        $this->field_close_date = $this->setUpField($tracker, 1003);

        $workflow = mock('Workflow');
        stub($workflow)->getFieldId()->returns($this->field_status->getId());

        $this->transition_factory = mock('TransitionFactory');
        stub($this->transition_factory)->isFieldUsedInTransitions($this->field_start_date)->returns(false);
        stub($this->transition_factory)->isFieldUsedInTransitions($this->field_close_date)->returns(true);

        $this->workflow_factory = partial_mock(
            'WorkflowFactory',
            array('getWorkflowByTrackerId'),
            array(
                $this->transition_factory,
                mock('TrackerFactory'),
                mock('Tracker_FormElementFactory'),
                mock('Tracker_Workflow_Trigger_RulesManager'),
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                \Mockery::mock(FrozenFieldsDao::class),
                Mockery::mock(StateFactory::class)
            )
        );
        stub($this->workflow_factory)->getWorkflowByTrackerId($tracker->getId())->returns($workflow);
    }

    private function setUpField(Tracker $tracker, $id)
    {
        $field = mock('Tracker_FormElement_Field_List');
        stub($field)->getTracker()->returns($tracker);
        stub($field)->getId()->returns($id);
        return $field;
    }

    public function itReturnsTrueIfTheFieldIsUsedToDescribeTheStatesOfTheWorkflow()
    {
        expect($this->transition_factory)->isFieldUsedInTransitions()->never();
        $this->assertTrue($this->workflow_factory->isFieldUsedInWorkflow($this->field_status));
    }

    public function itReturnsTrueIfTheFieldIsUsedInAPostAction()
    {
        expect($this->transition_factory)->isFieldUsedInTransitions()->once();
        $this->assertTrue($this->workflow_factory->isFieldUsedInWorkflow($this->field_close_date));
    }

    public function itReturnsFalseIfTheFieldIsNotUsedByTheWorkflow()
    {
        expect($this->transition_factory)->isFieldUsedInTransitions()->once();
        $this->assertFalse($this->workflow_factory->isFieldUsedInWorkflow($this->field_start_date));
    }
}

class WorkflowFactory_CacheTest extends TuleapTestCase
{

    /** @var WorkflowFactory */
    private $workflow_factory;

    public function setUp()
    {
        parent::setUp();
        $this->tracker_rules_manager = Mockery::mock(Tracker_RulesManager::class);
        $this->workflow_factory = partial_mock(
            'WorkflowFactory',
            array('getDao', 'getGlobalRulesManager'),
            array(
                mock('TransitionFactory'),
                stub('TrackerFactory')->getTrackerById()->returns(aMockTracker()->build()),
                mock('Tracker_FormElementFactory'),
                mock('Tracker_Workflow_Trigger_RulesManager'),
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                \Mockery::mock(FrozenFieldsDao::class),
                Mockery::mock(StateFactory::class)
            )
        );
        $this->dao = mock('Workflow_Dao');
        stub($this->workflow_factory)->getDao()->returns($this->dao);
        stub($this->workflow_factory)->getGlobalRulesManager()->returns($this->tracker_rules_manager);
    }

    public function itReturnsSameObjectWhenUsingSameTrackerId()
    {
        stub($this->dao)->searchByTrackerId(112)->returnsDar(
            array('tracker_id' => 112, 'workflow_id' => 34, 'field_id' => 56, 'is_used' => 1, 'is_legacy' => 0, 'is_advanced' => 1)
        );
        $this->assertSame(
            $this->workflow_factory->getWorkflowByTrackerId(112),
            $this->workflow_factory->getWorkflowByTrackerId(112)
        );
    }
}
