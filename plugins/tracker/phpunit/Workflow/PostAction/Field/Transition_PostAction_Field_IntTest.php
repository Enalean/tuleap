<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

final class Transition_PostAction_Field_IntTest extends TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;
    use \Tuleap\GlobalResponseMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Integer
     */
    private $field;

    /**
     * @var \Mockery\Mock
     */
    private $post_action;

    protected function setUp(): void
    {
        $value             = 0;
        $post_action_id    = 9348;
        $transition        = \Mockery::spy(\Transition::class);
        $this->field       = \Mockery::spy(\Tracker_FormElement_Field_Integer::class)
            ->shouldReceive('getId')->andReturns(1131)->getMock();

        $this->post_action = \Mockery::mock(
            \Transition_PostAction_Field_Int::class,
            [$transition, $post_action_id, $this->field, $value]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $dao               = \Mockery::spy(\Transition_PostAction_Field_IntDao::class);

        $this->post_action->shouldReceive('getDao')->andReturns($dao);
        $this->post_action->shouldReceive('isDefined')->andReturns($this->field);

        $GLOBALS['Language']->shouldReceive('getText')->with(
            'workflow_postaction',
            'field_value_set',
            ['Remaining Effort', 0]
        )->andReturns('field_value_set');
    }

    public function testBeforeShouldSetTheIntegerField(): void
    {
        $user = \Mockery::spy(\PFUser::class);

        $this->field->shouldReceive('getLabel')->andReturns('Remaining Effort');
        $this->field->shouldReceive('userCanRead')->with($user)->andReturns(true);
        $this->field->shouldReceive('userCanUpdate')->with($user)->andReturns(true);

        $expected    = 0;
        $fields_data = [
            'field_id' => 'value',
        ];

        $this->post_action->before($fields_data, $user);
        $this->assertEquals($expected, $fields_data[$this->field->getId()]);
    }

    public function testBeforeShouldBypassAndSetTheIntegerField(): void
    {
        $user = \Mockery::spy(\PFUser::class);

        $this->field->shouldReceive('getLabel')->andReturns('Remaining Effort')->getMock();
        $this->field->shouldReceive('userCanRead')->with($user)->andReturns(true)->getMock();
        $this->field->shouldReceive('userCanUpdate')->with($user)->andReturns(false)->getMock();

        $expected    = 0;
        $fields_data = [
            'field_id' => 'value',
        ];

        $this->post_action->before($fields_data, $user);
        $this->assertEquals($expected, $fields_data[$this->field->getId()]);
    }

    public function testBeforeShouldNOTDisplayFeedback(): void
    {
        $user = \Mockery::spy(\PFUser::class);

        $this->field->shouldReceive('getLabel')->andReturns('Remaining Effort')->getMock();
        $this->field->shouldReceive('userCanRead')->with($user)->andReturns(false)->getMock();

        $expected    = 0;
        $fields_data = [
            'field_id' => 'value',
        ];

        $this->post_action->before($fields_data, $user);
        $this->assertEquals($expected, $fields_data[$this->field->getId()]);
    }

    public function testItAcceptsValue0(): void
    {
        $post_action = new Transition_PostAction_Field_Int(
            Mockery::mock(Transition::class)->shouldReceive('getId')->andReturn(123)->getMock(),
            0,
            Mockery::mock(Tracker_FormElement_Field_Integer::class)->shouldReceive('getId')->andReturn(456)->getMock(),
            0
        );

        $this->assertTrue($post_action->isDefined());
    }
}
