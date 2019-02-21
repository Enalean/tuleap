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

namespace Tuleap\Project\Admin\ProjectUGroup;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class DynamicUGroupMembersUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface
     */
    private $dao;
    /**
     * @var \Mockery\MockInterface
     */
    private $ugroup_binding;
    /**
     * @var \Mockery\MockInterface
     */
    private $event_manager;

    protected function setUp() : void
    {
        $this->dao                                         = \Mockery::mock(UserPermissionsDao::class);
        $globals                                           = array_merge([], $GLOBALS);
        $this->ugroup_binding                              = \Mockery::mock(\UGroupBinding::class);
        $GLOBALS                                           = $globals;
        $this->event_manager                               = \Mockery::mock(\EventManager::class);
    }

    public function testTheLastProjectAdministratorCannotBeRemoved()
    {
        $updater = new DynamicUGroupMembersUpdater(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->ugroup_binding,
            $this->event_manager
        );

        $project      = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns(101);
        $admin_ugroup = \Mockery::mock(\ProjectUGroup::class);
        $admin_ugroup->shouldReceive('getId')->andReturns(\ProjectUGroup::PROJECT_ADMIN);
        $user         = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);

        $this->dao->shouldReceive('isThereOtherProjectAdmin')->andReturns(false);
        $this->expectException(CannotRemoveLastProjectAdministratorException::class);

        $updater->removeUser($project, $admin_ugroup, $user);
    }

    public function testAProjectAdministratorCanBeRemovedWhenItIsNotTheLastOne()
    {
        $updater = new DynamicUGroupMembersUpdater(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->ugroup_binding,
            $this->event_manager
        );

        $project      = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns(101);
        $admin_ugroup = \Mockery::mock(\ProjectUGroup::class);
        $admin_ugroup->shouldReceive('getId')->andReturns(\ProjectUGroup::PROJECT_ADMIN);
        $user         = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);

        $this->dao->shouldReceive('isThereOtherProjectAdmin')->andReturns(true);
        $this->event_manager->shouldReceive('processEvent')
            ->with(\Mockery::type(ApproveProjectAdministratorRemoval::class))->once();
        $this->dao->shouldReceive('removeUserFromProjectAdmin')->once();
        $this->event_manager->shouldReceive('processEvent')
            ->with(\Mockery::type(UserIsNoLongerProjectAdmin::class))->once();

        $updater->removeUser($project, $admin_ugroup, $user);
    }
}
