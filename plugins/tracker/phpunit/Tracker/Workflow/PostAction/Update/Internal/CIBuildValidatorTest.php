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
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

class CIBuildValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var CIBuildValidator */
    private $validator;
    /** @var PostActionCollection | Mockery\MockInterface */
    private $post_action_collection;

    protected function setUp()
    {
        $this->validator               = new CIBuildValidator();
        $this->post_action_collection  = Mockery::mock(PostActionCollection::class);
    }

    public function testValidateDoesNotThrowWhenValid()
    {
        $first_ci_build  = $this->createCIBuildWithId(1);
        $second_ci_build = $this->createCIBuildWithId(2);
        $this->post_action_collection->shouldReceive('getCIBuildActions')->andReturn(
            [$first_ci_build, $second_ci_build]
        );

        $this->validator->validate($this->post_action_collection);
    }

    /**
     * @expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\DuplicateCIBuildPostAction
     */
    public function testValidateThrowsWhenDuplicateCIBuildIds()
    {
        $first_ci_build   = $this->createCIBuildWithId(1);
        $same_id_ci_build = $this->createCIBuildWithId(1);
        $this->post_action_collection->shouldReceive('getCIBuildActions')->andReturn(
            [$first_ci_build, $same_id_ci_build]
        );
        $this->validator->validate($this->post_action_collection);
    }

    /**
     * @expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidCIBuildPostActionException
     */
    public function testValidateThrowsWhenInvalidJobUrl()
    {
        $invalid_ci_build = $this->createCIBuildWithUrl('not a URL');
        $this->post_action_collection->shouldReceive('getCIBuildActions')->andReturn([$invalid_ci_build]);

        $this->validator->validate($this->post_action_collection);
    }

    /**
     * @expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidCIBuildPostActionException
     */
    public function testValidateThrowsWhenEmptyJobUrl()
    {
        $invalid_ci_build = $this->createCIBuildWithUrl('');
        $this->post_action_collection->shouldReceive('getCIBuildActions')->andReturn([$invalid_ci_build]);

        $this->validator->validate($this->post_action_collection);
    }

    private function createCIBuildWithId(int $id)
    {
        $ci_build = Mockery::mock(CIBuild::class);
        $ci_build->shouldReceive('getId')->andReturn($id);
        $ci_build->shouldReceive('getJobUrl')->andReturn('https://example.com');
        return $ci_build;
    }

    private function createCIBuildWithUrl(string $job_url)
    {
        $ci_build = Mockery::mock(CIBuild::class);
        $ci_build->shouldReceive('getId');
        $ci_build->shouldReceive('getJobUrl')->andReturn($job_url);
        return $ci_build;
    }
}
