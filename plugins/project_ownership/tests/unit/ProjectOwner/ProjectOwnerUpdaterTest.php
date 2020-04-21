<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class ProjectOwnerUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = \Mockery::mock(ProjectOwnerDAO::class);
    }

    public function testProjectOwnerCanBeUpdated()
    {
        $this->dao->shouldReceive('save')->once();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns('102');
        $user->shouldReceive('isAdmin')->with('101')->andReturns(true);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns('101');

        $updater = new ProjectOwnerUpdater($this->dao, new DBTransactionExecutorPassthrough());
        $updater->updateProjectOwner($project, $user);
    }

    public function testProjectOwnerCannotBeSetIfNotAlreadyAProjectAdmin()
    {
        $this->dao->shouldReceive('save')->never();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns('102');
        $user->shouldReceive('isAdmin')->with('101')->andReturns(false);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns('101');

        $updater = new ProjectOwnerUpdater($this->dao, new DBTransactionExecutorPassthrough());

        $this->expectException(OnlyProjectAdministratorCanBeSetAsProjectOwnerException::class);

        $updater->updateProjectOwner($project, $user);
    }
}
