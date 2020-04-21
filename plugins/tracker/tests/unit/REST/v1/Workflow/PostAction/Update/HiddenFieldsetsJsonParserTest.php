<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction\Update;

require_once __DIR__ . '/../../../../../bootstrap.php';

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\IncompatibleWorkflowModeException;
use Workflow;

class HiddenFieldsetsJsonParserTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var HiddenFieldsetsJsonParser
     */
    private $parser;

    /**
     * @before
     */
    public function createParser()
    {
        $this->parser = new HiddenFieldsetsJsonParser();
    }

    public function testAcceptReturnsTrueWhenTypeMatches()
    {
        $this->assertTrue($this->parser->accept(["type" => "hidden_fieldsets"]));
    }

    public function testAcceptReturnsFalseWhenTypeDoesNotMatch()
    {
        $this->assertFalse($this->parser->accept(["type" => "set_date_value"]));
    }

    public function testParseReturnsNewHiddenFieldsetsValueBasedOnGivenJson()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);

        $hidden_fieldsets_value = $this->parser->parse(
            $workflow,
            [
                "id" => 2,
                "type" => "hidden_fieldsets",
                "fieldset_ids" => [43],
            ]
        );
        $expected_action = new HiddenFieldsetsValue([43]);
        $this->assertEquals($expected_action, $hidden_fieldsets_value);
    }

    public function testParseWhenIdNotProvided()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);

        $hidden_fieldsets_value = $this->parser->parse(
            $workflow,
            [
                "type" => "hidden_fieldsets",
                "fieldset_ids" => [43],
            ]
        );
        $expected_action = new HiddenFieldsetsValue([43]);
        $this->assertEquals($expected_action, $hidden_fieldsets_value);
    }

    public function testParseThrowsAnExceptionWhenNoFieldsetIdsProvided()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->parser->parse(
            $workflow,
            [
                "id" => 1,
                "type" => "hidden_fieldsets",
            ]
        );
    }

    public function testParseThrowsAnExceptionWhenFieldIdsIsNull()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->parser->parse(
            $workflow,
            [
                "id" => 1,
                "type" => "hidden_fieldsets",
                "fieldset_ids" => null,
            ]
        );
    }

    public function testParseThrowsAnExceptionWhenFieldsetIdIsAnEmptyArray()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->parser->parse(
            $workflow,
            [
                "id" => 1,
                "type" => "hidden_fieldsets",
                "fieldset_ids" => [],
            ]
        );
    }

    public function testParseThrowsAnExceptionWhenFieldsetIdIsNotAnArrayOfInt()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->parser->parse(
            $workflow,
            [
                "id" => 1,
                "type" => "hidden_fieldsets",
                "fieldset_ids" => [1, 'aaa'],
            ]
        );
    }

    public function testItThrowsAnExceptionIfWorkflowIsInAdvancedMode()
    {
        $workflow = \Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturnTrue();

        $this->expectException(IncompatibleWorkflowModeException::class);
        $this->parser->parse($workflow, ["type" => "hidden_fieldsets"]);
    }
}
