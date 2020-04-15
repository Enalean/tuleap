<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class TransitionFactoryTest extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var TransitionFactory */
    private $factory;

    /** @var Workflow_Transition_ConditionFactory */
    private $condition_factory;

    /**
     * @var EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $event_manager;

    private $postaction_factory;
    private $a_field_not_used_in_transitions;
    private $a_field_used_in_post_actions;
    private $a_field_used_in_conditions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->condition_factory  = \Mockery::spy(\Workflow_Transition_ConditionFactory::class);
        $this->postaction_factory = \Mockery::spy(\Transition_PostActionFactory::class);
        $this->event_manager      = \Mockery::spy(\EventManager::class);
        $this->factory            = \Mockery::mock(
            \TransitionFactory::class,
            [
                $this->condition_factory,
                $this->event_manager,
                new DBTransactionExecutorPassthrough()
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->factory->shouldReceive('getPostActionFactory')->andReturns($this->postaction_factory);

        $this->a_field_not_used_in_transitions = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->a_field_not_used_in_transitions->shouldReceive('getId')->andReturns(1002);

        $this->a_field_used_in_post_actions = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->a_field_used_in_post_actions->shouldReceive('getId')->andReturns(1003);

        $this->a_field_used_in_conditions = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->a_field_used_in_conditions->shouldReceive('getId')->andReturns(1004);

        $this->postaction_factory->shouldReceive('isFieldUsedInPostActions')->with($this->a_field_not_used_in_transitions)->andReturns(false);
        $this->postaction_factory->shouldReceive('isFieldUsedInPostActions')->with($this->a_field_used_in_post_actions)->andReturns(true);
        $this->postaction_factory->shouldReceive('isFieldUsedInPostActions')->with($this->a_field_used_in_conditions)->andReturns(false);

        $this->condition_factory->shouldReceive('isFieldUsedInConditions')->with($this->a_field_not_used_in_transitions)->andReturns(false);
        $this->condition_factory->shouldReceive('isFieldUsedInConditions')->with($this->a_field_used_in_post_actions)->andReturns(false);
        $this->condition_factory->shouldReceive('isFieldUsedInConditions')->with($this->a_field_used_in_conditions)->andReturns(true);
    }

    public function testItReturnsTrueIfFieldIsUsedInPostActions(): void
    {
        $this->assertTrue($this->factory->isFieldUsedInTransitions($this->a_field_used_in_post_actions));
    }

    public function testItReturnsTrueIfFieldIsUsedInConditions(): void
    {
        $this->assertTrue($this->factory->isFieldUsedInTransitions($this->a_field_used_in_conditions));
    }

    public function testItReturnsFalseIsNiotUsedInTransitions(): void
    {
        $this->assertFalse($this->factory->isFieldUsedInTransitions($this->a_field_not_used_in_transitions));
    }

    public function testDuplicate(): void
    {
        $field_value_new = \Mockery::spy(\Tracker_FormElement_Field_List_Value::class);
        $field_value_new->shouldReceive('getId')->andReturns(2066);
        $field_value_analyzed = \Mockery::spy(\Tracker_FormElement_Field_List_Value::class);
        $field_value_analyzed->shouldReceive('getId')->andReturns(2067);
        $field_value_accepted = \Mockery::spy(\Tracker_FormElement_Field_List_Value::class);
        $field_value_accepted->shouldReceive('getId')->andReturns(2068);

        $t1  = new Transition(1, 1, $field_value_new, $field_value_analyzed);
        $t2  = new Transition(2, 1, $field_value_analyzed, $field_value_accepted);
        $t3  = new Transition(3, 1, $field_value_analyzed, $field_value_new);
        $transitions = array($t1, $t2, $t3);

        $tf = \Mockery::mock(
            \TransitionFactory::class,
            [
                $this->condition_factory,
                $this->event_manager,
                new DBTransactionExecutorPassthrough()
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $values = array(
            2066  => 3066,
            2067  => 3067,
            2068  => 3068
        );

        $tf->shouldReceive('addTransition')->with(1, 3066, 3067)->once()->andReturn(101);
        $tf->shouldReceive('addTransition')->with(1, 3067, 3068)->once()->andReturn(102);
        $tf->shouldReceive('addTransition')->with(1, 3067, 3066)->once()->andReturn(103);

        $this->condition_factory->shouldReceive('duplicate')->with($t1, 101, array(), false, false)->once();
        $this->condition_factory->shouldReceive('duplicate')->with($t2, 102, array(), false, false)->once();
        $this->condition_factory->shouldReceive('duplicate')->with($t3, 103, array(), false, false)->once();

        $tpaf = \Mockery::spy(\Transition_PostActionFactory::class);
        $tpaf->shouldReceive('duplicate')->times(3);
        $tpaf->shouldReceive('duplicate')->with($t1, 101, array())->ordered();
        $tpaf->shouldReceive('duplicate')->with($t2, 102, array())->ordered();
        $tpaf->shouldReceive('duplicate')->with($t3, 103, array())->ordered();
        $tf->shouldReceive('getPostActionFactory')->andReturns($tpaf);

        $tf->duplicate($values, 1, $transitions, array(), false, false);
    }
}
