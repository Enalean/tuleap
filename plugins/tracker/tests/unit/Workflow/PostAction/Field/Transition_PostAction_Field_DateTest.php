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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\Field\Date\DateField;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Transition_PostAction_Field_DateTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    use \Tuleap\GlobalLanguageMock;
    use \Tuleap\GlobalResponseMock;

    private Tracker_FormElementFactory&MockObject $factory;

    private DateField&MockObject $field;

    private PFUser $current_user;

    #[\Override]
    protected function setUp(): void
    {
        $GLOBALS['Language']->method('getText')->with('system', 'datefmt_short')->willReturn(Tracker_FormElement_DateFormatter::DATE_FORMAT);

        $this->current_user = UserTestBuilder::buildWithDefaults();

        $this->field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);
        $this->field->method('getId')->willReturn(102);
        $this->field->method('getLabel')->willReturn('Close Date');
        $this->field->method('userCanRead')->with($this->current_user)->willReturn(true);
        $this->field->method('userCanUpdate')->with($this->current_user)->willReturn(true);

        $this->factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->factory->method('getFormElementById')->with($this->field->getId())->willReturn($this->field);
    }

    public function testBeforeShouldSetTheDate(): void
    {
        $this->field->method('formatDate')->with($_SERVER['REQUEST_TIME'])->willReturn('date-of-today');

        $expected = $this->field->formatDate($_SERVER['REQUEST_TIME']);

        $fields_data = ['field_id' => 'value'];
        $transition  = $this->createMock(\Transition::class);
        $id          = 1;
        $value_type  = Transition_PostAction_Field_Date::FILL_CURRENT_TIME;

        $post_action = $this->getMockBuilder(\Transition_PostAction_Field_Date::class)
            ->setConstructorArgs([$transition, $id, $this->field, $value_type])
            ->onlyMethods(['getFormElementFactory'])
            ->getMock();
        $post_action->method('getFormElementFactory')->willReturn($this->factory);

        $post_action->before($fields_data, $this->current_user);
        $this->assertEquals($expected, $fields_data[$this->field->getId()]);
    }

    public function testBeforeShouldClearTheDate(): void
    {
        $transition  = $this->createMock(\Transition::class);
        $field_id    = $this->field->getId();
        $id          = 1;
        $fields_data = [
            'field_id' => 'value',
            $field_id  => '1317817376',
        ];
        $value_type  = Transition_PostAction_Field_Date::CLEAR_DATE;

        $post_action = $this->getMockBuilder(\Transition_PostAction_Field_Date::class)
            ->setConstructorArgs([$transition, $id, $this->field, $value_type])
            ->onlyMethods(['getFormElementFactory'])
            ->getMock();
        $post_action->method('getFormElementFactory')->willReturn($this->factory);

        $this->field->method('formatDate');

        $post_action->before($fields_data, $this->current_user);
        $this->assertEquals('', $fields_data[$field_id]);
    }

    public function testBeforeShouldBypassAndSetTheDate(): void
    {
        $this->field->method('formatDate')->with($_SERVER['REQUEST_TIME'])->willReturn('date-of-today');

        $fields_data = ['field_id' => 'value'];
        $transition  = $this->createMock(\Transition::class);
        $field_id    = $this->field->getId();
        $id          = 1;
        $value_type  = Transition_PostAction_Field_Date::FILL_CURRENT_TIME;

        $post_action = $this->getMockBuilder(\Transition_PostAction_Field_Date::class)
            ->setConstructorArgs([$transition, $id, $this->field, $value_type])
            ->onlyMethods(['getFormElementFactory'])
            ->getMock();
        $post_action->method('getFormElementFactory')->willReturn($this->factory);

        $post_action->before($fields_data, $this->current_user);
        $this->assertEquals('date-of-today', $fields_data[$field_id]);
    }

    public function testBeforeShouldBypassAndClearTheDate(): void
    {
        $submitted_timestamp = 1317817376;
        $transition          = $this->createMock(\Transition::class);
        $field_id            = $this->field->getId();
        $id                  = 1;
        $fields_data         = [
            'field_id' => 'value',
            $field_id  => $submitted_timestamp,
        ];
        $value_type          = Transition_PostAction_Field_Date::CLEAR_DATE;

        $post_action = $this->getMockBuilder(\Transition_PostAction_Field_Date::class)
            ->setConstructorArgs([$transition, $id, $this->field, $value_type])
            ->onlyMethods(['getFormElementFactory'])
            ->getMock();
        $post_action->method('getFormElementFactory')->willReturn($this->factory);

        $this->field->method('formatDate');

        $post_action->before($fields_data, $this->current_user);
        $this->assertEquals('', $fields_data[$field_id]);
    }

    public function testBeforeShouldNOTDisplayFeedback(): void
    {
        $field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);
        $field->method('getId')->willReturn(102);
        $field->method('getLabel')->willReturn('Close Date');
        $field->method('userCanRead')->with($this->current_user)->willReturn(false);
        $field->method('formatDate');


        $expected    = $field->formatDate($_SERVER['REQUEST_TIME']);
        $transition  = $this->createMock(\Transition::class);
        $field_id    = $field->getId();
        $id          = 1;
        $fields_data = [
            'field_id' => 'value',
        ];
        $value_type  = Transition_PostAction_Field_Date::CLEAR_DATE;
        $post_action = $this->getMockBuilder(\Transition_PostAction_Field_Date::class)
            ->setConstructorArgs([$transition, $id, $field, $value_type])
            ->onlyMethods(['getFormElementFactory'])
            ->getMock();
        $post_action->method('getFormElementFactory')->willReturn($this->factory);
        $post_action->before($fields_data, $this->current_user);
        $this->assertEquals($expected, $fields_data[$field_id]);
    }
}
