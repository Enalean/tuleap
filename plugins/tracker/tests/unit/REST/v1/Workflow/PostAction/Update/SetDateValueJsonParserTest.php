<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction\Update;

use Transition_PostAction_Field_Date;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SetDateValueJsonParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SetDateValueJsonParser $parser;

    #[\PHPUnit\Framework\Attributes\Before]
    public function createParser(): void
    {
        $this->parser = new SetDateValueJsonParser();
    }

    public function testAcceptReturnsTrueWhenTypeMatches(): void
    {
        $this->assertTrue($this->parser->accept([
            'type' => 'set_field_value',
            'field_type' => 'date',
        ]));
    }

    public function testAcceptReturnsFalseWhenTypeDoesNotMatch(): void
    {
        $this->assertFalse($this->parser->accept(['type' => 'run_job']));
    }

    public function testAcceptReturnsFalseWhenFieldTypeDoesNotMatch(): void
    {
        $this->assertFalse($this->parser->accept([
            'type' => 'set_field_value',
            'field_type' => 'int',
        ]));
    }

    public function testAcceptReturnsFalseWithoutType(): void
    {
        $this->assertFalse($this->parser->accept([]));
    }

    public function testParseReturnsNewSetDateValueBasedOnGivenJson(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $set_date_value  = $this->parser->parse(
            $workflow,
            [
                'id' => 2,
                'type' => 'set_field_value',
                'field_type' => 'date',
                'field_id' => 43,
                'value' => '',
            ]
        );
        $expected_action = new SetDateValue(43, Transition_PostAction_Field_Date::CLEAR_DATE);
        $this->assertEquals($expected_action, $set_date_value);
    }

    public function testParseWhenIdNotProvided(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $set_date_value  = $this->parser->parse(
            $workflow,
            [
                'type' => 'set_field_value',
                'field_type' => 'date',
                'field_id' => 43,
                'value' => 'current',
            ]
        );
        $expected_action = new SetDateValue(43, Transition_PostAction_Field_Date::FILL_CURRENT_TIME);
        $this->assertEquals($expected_action, $set_date_value);
    }

    public function testParseReturnsNewSetDateValueWithoutIdWhenWorkflowIsNotAdvanced(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(false);

        $set_date_value  = $this->parser->parse(
            $workflow,
            [
                'id' => 2,
                'type' => 'set_field_value',
                'field_type' => 'date',
                'field_id' => 43,
                'value' => '',
            ]
        );
        $expected_action = new SetDateValue(43, Transition_PostAction_Field_Date::CLEAR_DATE);
        $this->assertEquals($expected_action, $set_date_value);
    }

    public function testParseThrowsWhenNoFieldIdProvided(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                'type' => 'set_field_value',
                'field_type' => 'date',
                'value' => 'current',
            ]
        );
    }

    public function testParseThrowsWhenFieldIdIsNull(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                'type' => 'set_field_value',
                'field_type' => 'date',
                'field_id' => null,
                'value' => 'current',
            ]
        );
    }

    public function testParseThrowsWhenFieldIdIsNotInt(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                'type' => 'set_field_value',
                'field_type' => 'date',
                'field_id' => 'not int',
                'value' => 'current',
            ]
        );
    }

    public function testParseThrowsWhenNoValueProvided(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                'type' => 'set_field_value',
                'field_type' => 'date',
                'field_id' => 43,
            ]
        );
    }

    public function testParseThrowsWhenValueIsNull(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                'type' => 'set_field_value',
                'field_type' => 'date',
                'field_id' => 43,
                'value' => null,
            ]
        );
    }

    public function testParseThrowsWhenValueIsNotString(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                'type' => 'set_field_value',
                'field_type' => 'date',
                'field_id' => 43,
                'value' => 99,
            ]
        );
    }

    public function testParseThrowsWhenValueIsNotSupported(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                'type' => 'set_field_value',
                'field_type' => 'date',
                'field_id' => 43,
                'value' => 'not supported',
            ]
        );
    }
}
