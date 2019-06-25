<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction\Update;

require_once __DIR__ . '/../../../../../bootstrap.php';

use Mockery;
use PHPUnit\Framework\TestCase;
use Transition_PostAction_Field_Date;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;
use Workflow;

class SetDateValueJsonParserTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var SetDateValueJsonParser
     */
    private $parser;

    /**
     * @before
     */
    public function createParser()
    {
        $this->parser = new SetDateValueJsonParser();
    }

    public function testAcceptReturnsTrueWhenTypeMatches()
    {
        $this->assertTrue($this->parser->accept([
            "type" => "set_field_value",
            "field_type" => "date"
        ]));
    }

    public function testAcceptReturnsFalseWhenTypeDoesNotMatch()
    {
        $this->assertFalse($this->parser->accept(["type" => "run_job"]));
    }

    public function testAcceptReturnsFalseWhenFieldTypeDoesNotMatch()
    {
        $this->assertFalse($this->parser->accept([
            "type" => "set_field_value",
            "field_type" => "int"
        ]));
    }

    public function testAcceptReturnsFalseWithoutType()
    {
        $this->assertFalse($this->parser->accept([]));
    }

    public function testParseReturnsNewSetDateValueBasedOnGivenJson()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $set_date_value  = $this->parser->parse(
            $workflow,
            [
                "id" => 2,
                "type" => "set_field_value",
                "field_type" => "date",
                "field_id" => 43,
                "value" => ""
            ]
        );
        $expected_action = new SetDateValue(43, Transition_PostAction_Field_Date::CLEAR_DATE);
        $this->assertEquals($expected_action, $set_date_value);
    }

    public function testParseWhenIdNotProvided()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $set_date_value  = $this->parser->parse(
            $workflow,
            [
                "type" => "set_field_value",
                "field_type" => "date",
                "field_id" => 43,
                "value" => "current"
            ]
        );
        $expected_action = new SetDateValue(43, Transition_PostAction_Field_Date::FILL_CURRENT_TIME);
        $this->assertEquals($expected_action, $set_date_value);
    }

    public function testParseReturnsNewSetDateValueWithoutIdWhenWorkflowIsNotAdvanced()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);

        $set_date_value  = $this->parser->parse(
            $workflow,
            [
                "id" => 2,
                "type" => "set_field_value",
                "field_type" => "date",
                "field_id" => 43,
                "value" => ""
            ]
        );
        $expected_action = new SetDateValue(43, Transition_PostAction_Field_Date::CLEAR_DATE);
        $this->assertEquals($expected_action, $set_date_value);
    }

    public function testParseThrowsWhenNoFieldIdProvided()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                "type" => "set_field_value",
                "field_type" => "date",
                "value" => "current"
            ]
        );
    }

    public function testParseThrowsWhenFieldIdIsNull()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                "type" => "set_field_value",
                "field_type" => "date",
                "field_id" => null,
                "value" => "current"
            ]
        );
    }

    public function testParseThrowsWhenFieldIdIsNotInt()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                "type" => "set_field_value",
                "field_type" => "date",
                "field_id" => "not int",
                "value" => "current"
            ]
        );
    }

    public function testParseThrowsWhenNoValueProvided()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                "type" => "set_field_value",
                "field_type" => "date",
                "field_id" => 43
            ]
        );
    }

    public function testParseThrowsWhenValueIsNull()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                "type" => "set_field_value",
                "field_type" => "date",
                "field_id" => 43,
                "value" => null
            ]
        );
    }

    public function testParseThrowsWhenValueIsNotString()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                "type" => "set_field_value",
                "field_type" => "date",
                "field_id" => 43,
                "value" => 99
            ]
        );
    }

    public function testParseThrowsWhenValueIsNotSupported()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                "type" => "set_field_value",
                "field_type" => "date",
                "field_id" => 43,
                "value" => "not supported"
            ]
        );
    }
}
