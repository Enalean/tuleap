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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TransitionFactoryTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private TransitionFactory $factory;

    private Workflow_Transition_ConditionFactory&MockObject $condition_factory;

    private EventManager&MockObject $event_manager;

    private Transition_PostActionFactory&MockObject $postaction_factory;
    private $a_field_not_used_in_transitions;
    private $a_field_used_in_post_actions;
    private $a_field_used_in_conditions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->condition_factory  = $this->createMock(\Workflow_Transition_ConditionFactory::class);
        $this->postaction_factory = $this->createMock(\Transition_PostActionFactory::class);
        $this->event_manager      = $this->createMock(\EventManager::class);
        $this->factory            = new \TransitionFactory(
            $this->condition_factory,
            $this->event_manager,
            new DBTransactionExecutorPassthrough(),
            $this->postaction_factory,
            $this->createMock(Workflow_TransitionDao::class),
        );

        $this->a_field_not_used_in_transitions = DateFieldBuilder::aDateField(1002)->build();

        $this->a_field_used_in_post_actions = DateFieldBuilder::aDateField(1003)->build();

        $this->a_field_used_in_conditions = DateFieldBuilder::aDateField(1004)->build();

        $this->postaction_factory->method('isFieldUsedInPostActions')->willReturnCallback(fn (Tracker_FormElement_Field $field) => match ($field) {
            $this->a_field_not_used_in_transitions => false,
            $this->a_field_used_in_post_actions => true,
            $this->a_field_used_in_conditions => false,
        });

        $this->condition_factory->method('isFieldUsedInConditions')->willReturnCallback(fn (Tracker_FormElement_Field $field) => match ($field) {
            $this->a_field_not_used_in_transitions => false,
            $this->a_field_used_in_post_actions => false,
            $this->a_field_used_in_conditions => true,
        });
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
        $tpaf = $this->createMock(\Transition_PostActionFactory::class);

        $field_value_new      = ListStaticValueBuilder::aStaticValue('new')->withId(2066)->build();
        $field_value_analyzed = ListStaticValueBuilder::aStaticValue('analyzed')->withId(2067)->build();
        $field_value_accepted = ListStaticValueBuilder::aStaticValue('accepted')->withId(2068)->build();

        $t1          = new Transition(1, 1, $field_value_new, $field_value_analyzed);
        $t2          = new Transition(2, 1, $field_value_analyzed, $field_value_accepted);
        $t3          = new Transition(3, 1, $field_value_analyzed, $field_value_new);
        $transitions = [$t1, $t2, $t3];

        $user_groups_mapping = \Tuleap\Project\Duplication\DuplicationUserGroupMapping::fromSameProjectWithoutMapping();

        $transition_factory = $this->getMockBuilder(\TransitionFactory::class)
            ->setConstructorArgs(
                [
                    $this->condition_factory,
                    $this->event_manager,
                    new DBTransactionExecutorPassthrough(),
                    $tpaf,
                    $this->createMock(Workflow_TransitionDao::class),
                ]
            )->onlyMethods(['addTransition'])
            ->getMock();

        $values = [
            2066  => 3066,
            2067  => 3067,
            2068  => 3068,
        ];

        $transition_factory->expects($this->exactly(3))->method('addTransition')->willReturnCallback(
            static fn ($workflow_id, $from_id, $to_id) => match (true) {
                $from_id === 3066 && $to_id === 3067 => 101,
                $from_id === 3067 && $to_id === 3068 => 102,
                $from_id === 3067 && $to_id === 3066 => 103,
            }
        );

        $this->condition_factory->method('duplicate')->willReturnCallback(
            static fn (Transition $from_transition, int $new_transition_id) => match (true) {
                $from_transition === $t1 && $new_transition_id === 101,
                $from_transition === $t2 && $new_transition_id === 102,
                $from_transition === $t3 && $new_transition_id === 103 => null
            }
        );

        $tpaf->expects($this->exactly(3))->method('duplicate')->willReturnCallback(
            static fn (Transition $from_transition, $to_transition_id, array $field_mapping) => match (true) {
                $from_transition === $t1 && $to_transition_id === 101,
                    $from_transition === $t2 && $to_transition_id === 102,
                    $from_transition === $t3 && $to_transition_id === 103 => null
            }
        );

        $transition_factory->duplicate($values, 1, $transitions, [], $user_groups_mapping);
    }
}
