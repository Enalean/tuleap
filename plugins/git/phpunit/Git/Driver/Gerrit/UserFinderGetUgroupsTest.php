<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UserFinderGetUgroupsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $permissions_manager;
    private $user_finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissions_manager = \Mockery::spy(\PermissionsManager::class);
        $this->user_finder         = new Git_Driver_Gerrit_UserFinder($this->permissions_manager);
    }

    public function testItAsksPermissionsToPermissionsManager(): void
    {
        $repository_id   = 12;
        $permission_type = Git::PERM_READ;

        $this->permissions_manager->shouldReceive('getAuthorizedUgroups')
            ->with($repository_id, $permission_type, false)
            ->once()
            ->andReturns(\TestHelper::emptyDar());

        $this->user_finder->getUgroups($repository_id, $permission_type);
    }

    public function testItReturnsUGroupIdsFromPermissionsManager(): void
    {
        $ugroup_id_120 = 120;
        $ugroup_id_115 = 115;
        $this->permissions_manager->shouldReceive('getAuthorizedUgroups')
            ->andReturns(\TestHelper::arrayToDar(array('ugroup_id' => $ugroup_id_115), array('ugroup_id' => $ugroup_id_120)));

        $ugroups = $this->user_finder->getUgroups('whatever', 'whatever');
        $this->assertEquals(
            array(
                $ugroup_id_115,
                $ugroup_id_120,
            ),
            $ugroups
        );
    }

    public function testItAlwaysReturnsTheProjectAdminGroupWhenGitAdministratorsAreRequested(): void
    {
        $project_admin_group_id = ProjectUGroup::PROJECT_ADMIN;

        $expected_ugroups = array($project_admin_group_id);
        $ugroups          = $this->user_finder->getUgroups('whatever', Git::SPECIAL_PERM_ADMIN);

        $this->assertEquals($expected_ugroups, $ugroups);
    }

    public function testItDoesntJoinWithUGroupTableWhenItFetchesGroupPermissionsInOrderToReturnSomethingWhenWeAreDeletingTheGroup(): void
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUgroups')
            ->with(\Mockery::any(), \Mockery::any(), false)
            ->once()
            ->andReturns(\TestHelper::emptyDar());

        $this->user_finder->getUgroups('whatever', 'whatever');
    }
}
