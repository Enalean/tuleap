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
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CIBuildJsonParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CIBuildJsonParser $parser;

    #[\PHPUnit\Framework\Attributes\Before]
    public function createParser(): void
    {
        $this->parser = new CIBuildJsonParser();
    }

    public function testAcceptReturnsTrueWhenTypeMatches(): void
    {
        $this->assertTrue($this->parser->accept(['type' => 'run_job']));
    }

    public function testAcceptReturnsFalseWhenTypeDoesNotMatch(): void
    {
        $this->assertFalse($this->parser->accept(['type' => 'set_date_value']));
    }

    public function testAcceptReturnsFalseWithoutType(): void
    {
        $this->assertFalse($this->parser->accept([]));
    }

    public function testParseReturnsNewCIBuildBasedOnGivenJson(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $ci_build = $this->parser->parse(
            $workflow,
            [
                'id' => 2,
                'type' => 'run_job',
                'job_url' => 'http://example.test',
            ]
        );
        $this->assertEquals(new CIBuildValue('http://example.test'), $ci_build);
    }

    public function testParseWhenIdNotProvided(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $ci_build = $this->parser->parse(
            $workflow,
            [
                'type' => 'run_job',
                'job_url' => 'http://example.test',
            ]
        );
        $this->assertEquals(new CIBuildValue('http://example.test'), $ci_build);
    }

    public function testParseReturnsNewCIBuildWithoutIdWhenWorkflowIsNotAdvanced(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(false);

        $ci_build = $this->parser->parse(
            $workflow,
            [
                'id' => 2,
                'type' => 'run_job',
                'job_url' => 'http://example.test',
            ]
        );
        $this->assertEquals(new CIBuildValue('http://example.test'), $ci_build);
    }

    public function testParseThrowsWhenNoJobUrlProvided(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse($workflow, ['type' => 'run_job']);
    }

    public function testParseThrowsWhenJobUrlIsNull(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                'type' => 'run_job',
                'job_url' => null,
            ]
        );
    }

    public function testParseThrowsWhenJobUrlIsNotString(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                'type' => 'run_job',
                'job_url' => 3,
            ]
        );
    }
}
