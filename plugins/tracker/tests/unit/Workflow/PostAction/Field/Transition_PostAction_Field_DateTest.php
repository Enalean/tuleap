<?php
/**
 * Copyright (c) Enalean, 2011 - present. All Rights Reserved.
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

final class Transition_PostAction_Field_DateTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;
    use \Tuleap\GlobalResponseMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Date
     */
    private $field;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $current_user;

    protected function setUp(): void
    {
        $GLOBALS['Language']->shouldReceive('getText')->with('system', 'datefmt_short')->andReturns(Tracker_FormElement_DateFormatter::DATE_FORMAT);
        $GLOBALS['Language']->shouldReceive('getText')->with('workflow_postaction', 'field_value_set', ['Close Date', 'date-of-today'])->andReturns('field_value_set');
        $GLOBALS['Language']->shouldReceive('getText')->with('workflow_postaction', 'field_clear', ['Close Date'])->andReturns('field_clear');

        $this->current_user = \Mockery::spy(\PFUser::class);

        $this->field = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->field->shouldReceive('getId')->andReturns(102);
        $this->field->shouldReceive('getLabel')->andReturns('Close Date');
        $this->field->shouldReceive('userCanRead')->with($this->current_user)->andReturns(true);
        $this->field->shouldReceive('userCanUpdate')->with($this->current_user)->andReturns(true);

        $this->factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->factory->shouldReceive('getFormElementById')->with($this->field->getId())->andReturns($this->field);
    }

    public function testBeforeShouldSetTheDate(): void
    {
        $this->field->shouldReceive('formatDate')->with($_SERVER['REQUEST_TIME'])->andReturns('date-of-today');

        $expected    = $this->field->formatDate($_SERVER['REQUEST_TIME']);

        $fields_data = array('field_id' => 'value');
        $transition  = \Mockery::spy(\Transition::class);
        $id          = 1;
        $value_type  = Transition_PostAction_Field_Date::FILL_CURRENT_TIME;

        $post_action = \Mockery::mock(
            \Transition_PostAction_Field_Date::class,
            [$transition, $id, $this->field, $value_type]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $post_action->shouldReceive('getFormElementFactory')->andReturns($this->factory);

        $post_action->before($fields_data, $this->current_user);
        $this->assertEquals($expected, $fields_data[$this->field->getId()]);
    }

    public function testBeforeShouldClearTheDate(): void
    {
        $transition  = \Mockery::spy(\Transition::class);
        $field_id    = $this->field->getId();
        $id          = 1;
        $fields_data = array(
            'field_id' => 'value',
            $field_id  => '1317817376',
        );
        $value_type = Transition_PostAction_Field_Date::CLEAR_DATE;

        $post_action = \Mockery::mock(
            \Transition_PostAction_Field_Date::class,
            [$transition, $id, $this->field, $value_type]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $post_action->shouldReceive('getFormElementFactory')->andReturns($this->factory);

        $post_action->before($fields_data, $this->current_user);
        $this->assertEquals('', $fields_data[$field_id]);
    }

    public function testBeforeShouldBypassAndSetTheDate(): void
    {
        $this->field->shouldReceive('formatDate')->with($_SERVER['REQUEST_TIME'])->andReturns('date-of-today');

        $fields_data = array('field_id' => 'value');
        $transition  = \Mockery::spy(\Transition::class);
        $field_id    = $this->field->getId();
        $id          = 1;
        $value_type  = Transition_PostAction_Field_Date::FILL_CURRENT_TIME;

        $post_action = \Mockery::mock(
            \Transition_PostAction_Field_Date::class,
            [$transition, $id, $this->field, $value_type]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $post_action->shouldReceive('getFormElementFactory')->andReturns($this->factory);

        $post_action->before($fields_data, $this->current_user);
        $this->assertEquals("date-of-today", $fields_data[$field_id]);
    }

    public function testBeforeShouldBypassAndClearTheDate(): void
    {
        $submitted_timestamp = 1317817376;
        $transition  = \Mockery::spy(\Transition::class);
        $field_id    = $this->field->getId();
        $id          = 1;
        $fields_data = array(
            'field_id' => 'value',
            $field_id  => $submitted_timestamp,
        );
        $value_type = Transition_PostAction_Field_Date::CLEAR_DATE;

        $post_action = \Mockery::mock(
            \Transition_PostAction_Field_Date::class,
            [$transition, $id, $this->field, $value_type]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $post_action->shouldReceive('getFormElementFactory')->andReturns($this->factory);

        $post_action->before($fields_data, $this->current_user);
        $this->assertEquals('', $fields_data[$field_id]);
    }

    public function testBeforeShouldNOTDisplayFeedback(): void
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $field->shouldReceive('getId')->andReturns(102);
        $field->shouldReceive('getLabel')->andReturns('Close Date');
        $field->shouldReceive('userCanRead')->with($this->current_user)->andReturns(false);


        $expected    = $field->formatDate($_SERVER['REQUEST_TIME']);
        $transition  = \Mockery::spy(\Transition::class);
        $field_id    = $field->getId();
        $id          = 1;
        $fields_data = array(
            'field_id' => 'value',
        );
        $value_type = Transition_PostAction_Field_Date::CLEAR_DATE;
        $post_action = \Mockery::mock(
            \Transition_PostAction_Field_Date::class,
            [$transition, $id, $field, $value_type]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $post_action->shouldReceive('getFormElementFactory')->andReturns($this->factory);
        $post_action->before($fields_data, $this->current_user);
        $this->assertEquals($expected, $fields_data[$field_id]);
    }
}
