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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction\Update;

use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\IncompatibleWorkflowModeException;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FrozenFieldsJsonParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FrozenFieldsJsonParser $parser;

    #[\PHPUnit\Framework\Attributes\Before]
    public function createParser(): void
    {
        $this->parser = new FrozenFieldsJsonParser();
    }

    public function testAcceptReturnsTrueWhenTypeMatches(): void
    {
        $this->assertTrue($this->parser->accept(['type' => 'frozen_fields']));
    }

    public function testAcceptReturnsFalseWhenTypeDoesNotMatch(): void
    {
        $this->assertFalse($this->parser->accept(['type' => 'set_date_value']));
    }

    public function testParseReturnsNewFrozenFieldsValueBasedOnGivenJson(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(false);

        $frozen_fields_value = $this->parser->parse(
            $workflow,
            [
                'id' => 2,
                'type' => 'frozen_fields',
                'field_ids' => [43],
            ]
        );
        $expected_action     = new FrozenFieldsValue([43]);
        $this->assertEquals($expected_action, $frozen_fields_value);
    }

    public function testParseWhenIdNotProvided(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(false);

        $frozen_fields_value = $this->parser->parse(
            $workflow,
            [
                'type' => 'frozen_fields',
                'field_ids' => [43],
            ]
        );
        $expected_action     = new FrozenFieldsValue([43]);
        $this->assertEquals($expected_action, $frozen_fields_value);
    }

    public function testParseThrowsAnExceptionWhenNoFieldIdsProvided(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(false);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->parser->parse(
            $workflow,
            [
                'id' => 1,
                'type' => 'frozen_fields',
            ]
        );
    }

    public function testParseThrowsAnExceptionWhenFieldIdsIsNull(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(false);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->parser->parse(
            $workflow,
            [
                'id' => 1,
                'type' => 'frozen_fields',
                'field_ids' => null,
            ]
        );
    }

    public function testParseThrowsAnExceptionWhenFieldIdIsAnEmptyArray(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(false);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->parser->parse(
            $workflow,
            [
                'id' => 1,
                'type' => 'frozen_fields',
                'field_ids' => [],
            ]
        );
    }

    public function testParseThrowsAnExceptionWhenFieldIdIsNotAnArrayOfInt(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(false);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->parser->parse(
            $workflow,
            [
                'id' => 1,
                'type' => 'frozen_fields',
                'field_ids' => [1, 'aaa'],
            ]
        );
    }

    public function testItThrowsAnExceptionIfWorkflowIsInAdvancedMode(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(IncompatibleWorkflowModeException::class);
        $this->parser->parse($workflow, ['type' => 'frozen_fields']);
    }
}
