<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\ProjectOwnership\ProjectOwner;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ProjectOwnerRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface
     */
    private $dao;
    /**
     * @var \Mockery\MockInterface
     */
    private $user_manager;

    protected function setUp(): void
    {
        $this->dao          = \Mockery::mock(ProjectOwnerDAO::class);
        $this->user_manager = \Mockery::mock(\UserManager::class);
    }

    public function testProjectOwnerCanBeRetrieved()
    {
        $this->dao->shouldReceive('searchByProjectID')->andReturns(['user_id' => 101, 'project_id' => 102]);
        $expected_user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserById')->andReturns($expected_user);

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns(102);

        $retriever    = new ProjectOwnerRetriever($this->dao, $this->user_manager);
        $project_owner = $retriever->getProjectOwner($project);

        $this->assertSame($expected_user, $project_owner);
    }

    public function testNoProjectOwner()
    {
        $this->dao->shouldReceive('searchByProjectID')->andReturns([]);

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns(102);

        $retriever = new ProjectOwnerRetriever($this->dao, $this->user_manager);
        $this->assertNull($retriever->getProjectOwner($project));
    }
}
