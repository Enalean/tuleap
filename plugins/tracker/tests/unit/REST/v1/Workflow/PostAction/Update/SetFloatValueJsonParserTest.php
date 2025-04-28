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

use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SetFloatValueJsonParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SetFloatValueJsonParser $parser;

    #[\PHPUnit\Framework\Attributes\Before]
    public function createParser(): void
    {
        $this->parser = new SetFloatValueJsonParser();
    }

    public function testAcceptReturnsTrueWhenTypeMatches(): void
    {
        $this->assertTrue($this->parser->accept([
            'type' => 'set_field_value',
            'field_type' => 'float',
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
            'field_type' => 'date',
        ]));
    }

    public function testAcceptReturnsFalseWithoutType(): void
    {
        $this->assertFalse($this->parser->accept([]));
    }

    public function testParseReturnsNewSetFloatValueBasedOnGivenJson(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $set_date_value  = $this->parser->parse(
            $workflow,
            [
                'id' => 2,
                'type' => 'set_field_value',
                'field_type' => 'float',
                'field_id' => 43,
                'value' => 1.23,
            ]
        );
        $expected_action = new SetFloatValue(43, 1.23);
        $this->assertEquals($expected_action, $set_date_value);
    }

    public function testParseAcceptsIntValues(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $set_date_value  = $this->parser->parse(
            $workflow,
            [
                'id' => 2,
                'type' => 'set_field_value',
                'field_type' => 'float',
                'field_id' => 43,
                'value' => 1,
            ]
        );
        $expected_action = new SetFloatValue(43, 1);
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
                'field_type' => 'float',
                'field_id' => 43,
                'value' => 1,
            ]
        );
        $expected_action = new SetFloatValue(43, 1);
        $this->assertEquals($expected_action, $set_date_value);
    }

    public function testParseReturnsNewSetFloatValueWithoutIdWhenWorkflowIsNotAdvanced(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(false);

        $set_date_value  = $this->parser->parse(
            $workflow,
            [
                'id' => 2,
                'type' => 'set_field_value',
                'field_type' => 'float',
                'field_id' => 43,
                'value' => 1,
            ]
        );
        $expected_action = new SetFloatValue(43, 1);
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
                'field_type' => 'float',
                'value' => 1,
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
                'field_type' => 'float',
                'field_id' => null,
                'value' => 1,
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
                'field_type' => 'float',
                'field_id' => 'not int',
                'value' => 1,
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
                'field_type' => 'float',
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
                'field_type' => 'float',
                'field_id' => 43,
                'value' => null,
            ]
        );
    }

    public function testParseThrowsWhenValueIsNotNumeric(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                'type' => 'set_field_value',
                'field_type' => 'float',
                'field_id' => 43,
                'value' => 'not numeric',
            ]
        );
    }
}
