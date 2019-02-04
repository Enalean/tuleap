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

use PHPUnit\Framework\TestCase;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetIntValue;

class SetIntValueJsonParserTest extends TestCase
{
    /**
     * @var SetIntValueJsonParser
     */
    private $parser;

    /**
     * @before
     */
    public function createParser()
    {
        $this->parser = new SetIntValueJsonParser();
    }

    public function testAcceptReturnsTrueWhenTypeMatches()
    {
        $this->assertTrue($this->parser->accept([
            "type" => "set_field_value",
            "field_type" => "int"
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
            "field_type" => "date"
        ]));
    }

    public function testAcceptReturnsFalseWithoutType()
    {
        $this->assertFalse($this->parser->accept([]));
    }

    public function testParseReturnsNewSetIntValueBasedOnGivenJson()
    {
        $set_date_value  = $this->parser->parse([
            "id" => 2,
            "type" => "set_field_value",
            "field_type" => "int",
            "field_id" => 43,
            "value" => 1
        ]);
        $expected_action = new SetIntValue(2, 43, 1);
        $this->assertEquals($expected_action, $set_date_value);
    }

    public function testParseWhenIdNotProvided()
    {
        $set_date_value  = $this->parser->parse([
            "type" => "set_field_value",
            "field_type" => "date",
            "field_id" => 43,
            "value" => 1
        ]);
        $expected_action = new SetIntValue(null, 43, 1);
        $this->assertEquals($expected_action, $set_date_value);
    }

    public function testParseThrowsWhenIdIsNotInt()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse([
            "id" => "not int",
            "type" => "set_field_value",
            "field_type" => "date",
            "field_id" => 43,
            "value" => 1
        ]);
    }

    public function testParseThrowsWhenNoFieldIdProvided()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse([
            "type" => "set_field_value",
            "field_type" => "date",
            "value" => 1
        ]);
    }

    public function testParseThrowsWhenFieldIdIsNull()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse([
            "type" => "set_field_value",
            "field_type" => "date",
            "field_id" => null,
            "value" => 1
        ]);
    }

    public function testParseThrowsWhenFieldIdIsNotInt()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse([
            "type" => "set_field_value",
            "field_type" => "date",
            "field_id" => "not int",
            "value" => 1
        ]);
    }

    public function testParseThrowsWhenNoValueProvided()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse([
            "type" => "set_field_value",
            "field_type" => "date",
            "field_id" => 43
        ]);
    }

    public function testParseThrowsWhenValueIsNull()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse([
            "type" => "set_field_value",
            "field_type" => "date",
            "field_id" => 43,
            "value" => null
        ]);
    }

    public function testParseThrowsWhenValueIsNotNumeric()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse([
            "type" => "set_field_value",
            "field_type" => "date",
            "field_id" => 43,
            "value" => "not int"
        ]);
    }

    public function testParseThrowsWhenValueIsFloat()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse([
            "type" => "set_field_value",
            "field_type" => "date",
            "field_id" => 43,
            "value" => 1.23
        ]);
    }
}
