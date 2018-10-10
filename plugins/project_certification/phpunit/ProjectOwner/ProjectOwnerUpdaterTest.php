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

namespace Tuleap\ProjectCertification\ProjectOwner;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ProjectOwnerUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\MockInterface
     */
    private $dao;
    /**
     * @var \Mockery\Matcher\Closure
     */
    private $mockery_matcher_callback_wrapped_operations;

    protected function setUp()
    {
        $this->dao                                         = \Mockery::mock(ProjectOwnerDAO::class);
        $this->mockery_matcher_callback_wrapped_operations = \Mockery::on(
            function (callable $operations) {
                $operations($this->dao);
                return true;
            }
        );
    }

    public function testProjectOwnerCanBeUpdated()
    {
        $this->dao->shouldReceive('save')->once();
        $this->dao->shouldReceive('wrapAtomicOperations')
            ->with($this->mockery_matcher_callback_wrapped_operations);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns('102');
        $user->shouldReceive('isAdmin')->with('101')->andReturns(true);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns('101');

        $updater = new ProjectOwnerUpdater($this->dao);
        $updater->updateProjectOwner($project, $user);
    }

    /**
     * @expectedException \Tuleap\ProjectCertification\ProjectOwner\OnlyProjectAdministratorCanBeSetAsProjectOwnerException
     */
    public function testProjectOwnerCannotBeSetIfNotAlreadyAProjectAdmin()
    {
        $this->dao->shouldReceive('save')->never();
        $this->dao->shouldReceive('wrapAtomicOperations')
            ->with($this->mockery_matcher_callback_wrapped_operations);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns('102');
        $user->shouldReceive('isAdmin')->with('101')->andReturns(false);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns('101');

        $updater = new ProjectOwnerUpdater($this->dao);
        $updater->updateProjectOwner($project, $user);
    }
}
