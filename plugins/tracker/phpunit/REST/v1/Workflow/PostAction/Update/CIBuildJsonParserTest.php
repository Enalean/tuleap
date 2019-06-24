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
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Workflow;

class CIBuildJsonParserTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var CIBuildJsonParser
     */
    private $parser;

    /**
     * @before
     */
    public function createParser()
    {
        $this->parser = new CIBuildJsonParser();
    }

    public function testAcceptReturnsTrueWhenTypeMatches()
    {
        $this->assertTrue($this->parser->accept(["type" => "run_job"]));
    }

    public function testAcceptReturnsFalseWhenTypeDoesNotMatch()
    {
        $this->assertFalse($this->parser->accept(["type" => "set_date_value"]));
    }

    public function testAcceptReturnsFalseWithoutType()
    {
        $this->assertFalse($this->parser->accept([]));
    }

    public function testParseReturnsNewCIBuildBasedOnGivenJson()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $ci_build = $this->parser->parse(
            $workflow,
            [
                "id" => 2,
                "type" => "run_job",
                "job_url" => "http://example.test",
            ]
        );
        $this->assertEquals(new CIBuildValue("http://example.test"), $ci_build);
    }

    public function testParseWhenIdNotProvided()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $ci_build = $this->parser->parse(
            $workflow,
            [
                "type" => "run_job",
                "job_url" => "http://example.test",
            ]
        );
        $this->assertEquals(new CIBuildValue("http://example.test"), $ci_build);
    }

    public function testParseReturnsNewCIBuildWithoutIdWhenWorkflowIsNotAdvanced()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);

        $ci_build = $this->parser->parse(
            $workflow,
            [
                "id" => 2,
                "type" => "run_job",
                "job_url" => "http://example.test",
            ]
        );
        $this->assertEquals(new CIBuildValue("http://example.test"), $ci_build);
    }

    public function testParseThrowsWhenNoJobUrlProvided()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse($workflow, ["type" => "run_job"]);
    }

    public function testParseThrowsWhenJobUrlIsNull()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                "type" => "run_job",
                "job_url" => null
            ]
        );
    }

    public function testParseThrowsWhenJobUrlIsNotString()
    {
        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->parser->parse(
            $workflow,
            [
                "type" => "run_job",
                "job_url" => 3
            ]
        );
    }
}
