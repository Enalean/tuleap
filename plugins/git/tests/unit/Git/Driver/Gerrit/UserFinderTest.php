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
class UserFinderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Git_Driver_Gerrit_UserFinder */
    protected $user_finder;

    /** @var PermissionsManager */
    protected $permissions_manager;

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var GitRepository **/
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissions_manager = \Mockery::spy(\PermissionsManager::class);
        $this->ugroup_manager      = \Mockery::spy(\UGroupManager::class);
        $this->user_finder = new Git_Driver_Gerrit_UserFinder($this->permissions_manager);
        $this->project_id = 666;
        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturns(5);
        $this->repository->shouldReceive('getProjectId')->andReturns($this->project_id);
    }

    public function testItReturnsFalseForSpecialAdminPerms(): void
    {
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::SPECIAL_PERM_ADMIN, $this->repository);
        $this->assertFalse($allowed);
    }

    public function testItReturnsFalseIfRegisteredUsersGroupIsNotContainedInTheAllowedOnes(): void
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUgroups')->andReturns(array(
            array('ugroup_id' => ProjectUGroup::PROJECT_MEMBERS),
            array('ugroup_id' => ProjectUGroup::PROJECT_ADMIN),
        ));
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository);
        $this->assertFalse($allowed);
    }

    public function testItReturnsTrueIfRegisteredUsersGroupIsContainedInTheAllowedOnes(): void
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUgroups')->andReturns(array(
            array('ugroup_id' => ProjectUGroup::PROJECT_MEMBERS),
            array('ugroup_id' => ProjectUGroup::REGISTERED),
        ));
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository);
        $this->assertTrue($allowed);
    }

    public function testItReturnsTrueIfAllUsersAreContainedInTheAllowedOnes(): void
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUgroups')->andReturns(array(
            array('ugroup_id' => ProjectUGroup::ANONYMOUS),
        ));
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository);
        $this->assertTrue($allowed);
    }
}
