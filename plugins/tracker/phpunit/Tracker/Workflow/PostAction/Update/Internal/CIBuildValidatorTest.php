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
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

require_once __DIR__ . '/../../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CIBuildValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var CIBuildValidator */
    private $ci_build_validator;
    /** @var PostActionIdValidator | Mockery\MockInterface */
    private $ids_validator;

    protected function setUp()
    {
        $this->ids_validator      = Mockery::mock(PostActionIdValidator::class);
        $this->ids_validator->shouldReceive('validate')->byDefault();
        $this->ci_build_validator = new CIBuildValidator($this->ids_validator);
    }

    public function testValidateDoesNotThrowWhenValid()
    {
        $first_ci_build  = $this->createCIBuild();
        $second_ci_build = $this->createCIBuild();
        $this->ids_validator->shouldReceive('validate');

        $this->ci_build_validator->validate($first_ci_build, $second_ci_build);
    }

    /**
     * @expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     */
    public function testValidateThrowsWhenInvalidJobUrl()
    {
        $invalid_ci_build = $this->createCIBuildWithUrl('not an URL');

        $this->ci_build_validator->validate($invalid_ci_build);
    }

    /**
     * @expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     */
    public function testValidateThrowsWhenEmptyJobUrl()
    {
        $invalid_ci_build = $this->createCIBuildWithUrl('');

        $this->ci_build_validator->validate($invalid_ci_build);
    }

    /**
     * @@expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     */
    public function testValidateWrapsDuplicatePostActionException()
    {
        $ci_build = $this->createCIBuild();
        $this->ids_validator
            ->shouldReceive('validate')
            ->andThrow(new DuplicatePostActionException());

        $this->ci_build_validator->validate($ci_build);
    }

    private function createCIBuild()
    {
        $ci_build = Mockery::mock(CIBuild::class);
        return $ci_build->shouldReceive('getJobUrl')->andReturn('https://example.com')
            ->getMock();
    }

    private function createCIBuildWithUrl(string $job_url)
    {
        $ci_build = Mockery::mock(CIBuild::class);
        $ci_build->shouldReceive('getJobUrl')->andReturn($job_url);
        return $ci_build;
    }
}
