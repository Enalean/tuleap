<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UGroupGetMembersTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    protected $garfield;
    protected $goofy;
    protected $garfield_incomplete_row = ['user_id' => 1234, 'user_name' => 'garfield'];
    protected $goofy_incomplete_row    = ['user_id' => 5677, 'user_name' => 'goofy'];

    protected function setUp(): void
    {
        parent::setUp();
        $user_manager = \Mockery::spy(\UserManager::class);
        UserManager::setInstance($user_manager);

        $this->garfield = new PFUser($this->garfield_incomplete_row);
        $this->goofy    = new PFUser($this->goofy_incomplete_row);
        $user_manager->shouldReceive('getUserById')->with($this->garfield_incomplete_row['user_id'])->andReturns($this->garfield);
        $user_manager->shouldReceive('getUserById')->with($this->goofy_incomplete_row['user_id'])->andReturns($this->goofy);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        parent::tearDown();
    }

    public function testItIsEmptyWhenTheGroupIsEmpty(): void
    {
        $id     = 333;
        $row    = ['ugroup_id' => $id];
        $ugroup = new ProjectUGroup($row);
        $ugroup->setUGroupUserDao(\Mockery::spy(\UGroupUserDao::class)->shouldReceive('searchUserByStaticUGroupId')->with($id)->andReturns(\TestHelper::emptyDar())->getMock());
        $this->assertCount(0, $ugroup->getMembers());
        $this->assertCount(0, $ugroup->getMembersUserName());
    }

    public function testItReturnsTheMembersOfStaticGroups(): void
    {
        $id     = 333;
        $row    = ['ugroup_id' => $id];
        $ugroup = new ProjectUGroup($row);
        $ugroup->setUGroupUserDao(
            \Mockery::spy(\UGroupUserDao::class)->shouldReceive('searchUserByStaticUGroupId')->with($id)->andReturns(\TestHelper::arrayToDar($this->garfield_incomplete_row, $this->goofy_incomplete_row))->getMock()
        );
        $this->assertNotEmpty($ugroup->getMembers());
        $this->assertCount(2, $ugroup->getMembers());

        $this->assertNotEmpty($ugroup->getMembersUserName());
        $this->assertEquals(['garfield', 'goofy'], $ugroup->getMembersUserName());
    }

    public function testItReturnsTheMembersOfDynamicGroups(): void
    {
        $id       = 1;
        $group_id = 555;
        $row      = ['ugroup_id' => $id, 'group_id' => $group_id];
        $ugroup   = new ProjectUGroup($row);
        $ugroup->setUGroupUserDao(
            \Mockery::spy(\UGroupUserDao::class)->shouldReceive('searchUserByDynamicUGroupId')->with($id, $group_id)->andReturns(\TestHelper::arrayToDar($this->garfield_incomplete_row, $this->goofy_incomplete_row))->getMock()
        );
        $this->assertNotEmpty($ugroup->getMembers());
        $this->assertCount(2, $ugroup->getMembers());

        $this->assertNotEmpty($ugroup->getMembersUserName());
        $this->assertEquals(['garfield', 'goofy'], $ugroup->getMembersUserName());
    }
}
