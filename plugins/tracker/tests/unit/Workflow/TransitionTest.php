<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TransitionTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private $id          = 1;
    private $workflow_id = 2;
    private $from;
    private $to;
    private $transition;
    private $field;
    private $date_post_action;
    private $current_user;

    protected function setUp(): void
    {
        $this->from = ListStaticValueBuilder::aStaticValue('value')->withId(123)->build();
        $this->to   = ListStaticValueBuilder::aStaticValue('value')->withId(456)->build();
        PermissionsManager::setInstance($this->createMock(\PermissionsManager::class));

        $this->transition       = new Transition($this->id, $this->workflow_id, $this->from, $this->to);
        $this->field            = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $this->date_post_action = $this->createMock(\Transition_PostAction_Field_Date::class);
        $this->date_post_action->method('bypassPermissions')->willReturn(true);
        $this->current_user = $this->createMock(\PFUser::class);
    }

    protected function tearDown(): void
    {
        PermissionsManager::clearInstance();
    }

    public function testEquals(): void
    {
        $field_value_new      = ListStaticValueBuilder::aStaticValue('new')->withId(2066)->build();
        $field_value_analyzed = ListStaticValueBuilder::aStaticValue('analyzed')->withId(2067)->build();
        $field_value_accepted = ListStaticValueBuilder::aStaticValue('accepted')->withId(2068)->build();

        $t1 = new Transition(1, 2, $field_value_new, $field_value_analyzed);
        $t2 = new Transition(1, 2, $field_value_analyzed, $field_value_accepted);
        $t3 = new Transition(1, 2, $field_value_analyzed, $field_value_new);
        $t4 = new Transition(1, 2, $field_value_new, $field_value_analyzed); // equals $t1
        $t5 = new Transition(1, 2, null, $field_value_analyzed);
        $t6 = new Transition(1, 2, null, $field_value_analyzed);

        $this->assertTrue($t1->equals($t1));
        $this->assertTrue($t2->equals($t2));
        $this->assertTrue($t3->equals($t3));
        $this->assertTrue($t4->equals($t1));
        $this->assertTrue($t5->equals($t6));

        $this->assertFalse($t1->equals($t2));
        $this->assertFalse($t2->equals($t1));
        $this->assertFalse($t2->equals($t3));
        $this->assertFalse($t4->equals($t5));
    }

    public function testBeforeShouldTriggerActions(): void
    {
        $current_user = $this->createMock(\PFUser::class);

        $field_value_new      = ListStaticValueBuilder::aStaticValue('new')->withId(2066)->build();
        $field_value_analyzed = ListStaticValueBuilder::aStaticValue('analyzed')->withId(2067)->build();

        $fields_data = ['field_id' => 'value'];

        $t1 = new Transition(1, 2, $field_value_new, $field_value_analyzed);
        $t1->setConditions(new Workflow_Transition_ConditionsCollection());

        $a1 = $this->createMock(\Transition_PostAction::class);
        $a2 = $this->createMock(\Transition_PostAction::class);

        $t1->setPostActions([$a1, $a2]);

        $a1->expects($this->once())->method('before')->with($fields_data, $current_user);
        $a2->expects($this->once())->method('before')->with($fields_data, $current_user);

        $t1->before($fields_data, $current_user);
    }

    public function testAfterShouldTriggerActions(): void
    {
        $field_value_new      = ListStaticValueBuilder::aStaticValue('new')->withId(2066)->build();
        $field_value_analyzed = ListStaticValueBuilder::aStaticValue('analyzed')->withId(2067)->build();

        $transition = new Transition(1, 2, $field_value_new, $field_value_analyzed);
        $transition->setConditions(new Workflow_Transition_ConditionsCollection());

        $post_action_1 = $this->createMock(\Transition_PostAction::class);
        $post_action_2 = $this->createMock(\Transition_PostAction::class);

        $transition->setPostActions([$post_action_1, $post_action_2]);

        $post_action_1->expects($this->once())->method('after');
        $post_action_2->expects($this->once())->method('after');

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);

        $transition->after($changeset);
    }

    public function testItReturnsTrueWhenConditionsAreValid(): void
    {
        $transition  = new Transition($this->id, $this->workflow_id, $this->from, $this->to);
        $fields_data = [];
        $artifact    = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $conditions  = $this->createMock(\Workflow_Transition_ConditionsCollection::class);
        $conditions->method('validate')->willReturn(true);
        $transition->setConditions($conditions);
        $this->assertTrue($transition->validate($fields_data, $artifact, '', $this->current_user));
    }

    public function testItReturnsFalseWhenConditionsAreNotValid(): void
    {
        $transition  = new Transition($this->id, $this->workflow_id, $this->from, $this->to);
        $fields_data = [];
        $artifact    = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $conditions  = $this->createMock(\Workflow_Transition_ConditionsCollection::class);
        $conditions->method('validate')->willReturn(false);
        $transition->setConditions($conditions);
        $this->assertFalse($transition->validate($fields_data, $artifact, '', $this->current_user));
    }

    public function testItBypassesPermission(): void
    {
        $posts_actions = [$this->date_post_action];

        $this->transition->setPostActions($posts_actions);
        $this->assertTrue($this->transition->bypassPermissions($this->field));
    }

    public function testItBypassesPermissionIfThereIsACIJob(): void
    {
        $ci_job = $this->createMock(\Transition_PostAction_CIBuild::class);
        $ci_job->method('bypassPermissions');

        $posts_actions = [$ci_job, $this->date_post_action];

        $this->transition->setPostActions($posts_actions);
        $this->assertTrue($this->transition->bypassPermissions($this->field));
    }
}
